<?php
class RateLimiter
{
    private $storageDir;
    private $requestsPerMinute;
    private $clientIp;
    
    /**
     * Constructor
     * 
     * @param int $requestsPerMinute Maximum requests allowed per minute
     * @param string $storageDir Directory to store rate limit data
     */
    public function __construct($requestsPerMinute = 60, $storageDir = null)
    {
        $this->requestsPerMinute = $requestsPerMinute;
        $this->storageDir = $storageDir ?: sys_get_temp_dir() . '/rate_limits';
        $this->clientIp = $this->getClientIp();
        
        // Create storage directory if it doesn't exist
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Check if the client has exceeded rate limits
     * 
     * @return bool True if rate limit exceeded, false otherwise
     */
    public function isLimitExceeded()
    {
        $limitFile = $this->getLimitFile();
        
        if (!file_exists($limitFile)) {
            $this->updateLimitFile($limitFile, []);
            return false;
        }
        
        $requestData = json_decode(file_get_contents($limitFile), true) ?: [];
        $requestData = $this->cleanOldRequests($requestData);
        
        // Check if rate limit exceeded
        if (count($requestData) >= $this->requestsPerMinute) {
            return true;
        }
        
        // Add current request
        $requestData[] = time();
        $this->updateLimitFile($limitFile, $requestData);
        
        return false;
    }
    
    /**
     * Get remaining number of requests allowed
     * 
     * @return int Number of requests remaining
     */
    public function getRemainingRequests()
    {
        $limitFile = $this->getLimitFile();
        
        if (!file_exists($limitFile)) {
            return $this->requestsPerMinute;
        }
        
        $requestData = json_decode(file_get_contents($limitFile), true) ?: [];
        $requestData = $this->cleanOldRequests($requestData);
        
        return max(0, $this->requestsPerMinute - count($requestData));
    }
    
    /**
     * Get reset time in seconds
     * 
     * @return int Seconds until rate limit resets
     */
    public function getResetTime()
    {
        $limitFile = $this->getLimitFile();
        
        if (!file_exists($limitFile)) {
            return 0;
        }
        
        $requestData = json_decode(file_get_contents($limitFile), true) ?: [];
        
        if (empty($requestData)) {
            return 0;
        }
        
        $oldestTimestamp = min($requestData);
        return max(0, 60 - (time() - $oldestTimestamp));
    }
    
    /**
     * Clean old requests older than 1 minute
     * 
     * @param array $requestData Array of request timestamps
     * @return array Cleaned request data
     */
    private function cleanOldRequests($requestData)
    {
        $minTime = time() - 60;
        return array_filter($requestData, function($timestamp) use ($minTime) {
            return $timestamp >= $minTime;
        });
    }
    
    /**
     * Get rate limit file path for current client
     * 
     * @return string Path to rate limit file
     */
    private function getLimitFile()
    {
        return $this->storageDir . '/' . md5($this->clientIp) . '.json';
    }
    
    /**
     * Update rate limit file with new data
     * 
     * @param string $file File path
     * @param array $data Request data to save
     */
    private function updateLimitFile($file, $data)
    {
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return $ip;
    }
}
?> 