<?php
class BaseController
{
    protected $rateLimiter;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize rate limiter if enabled
        if (defined('RATE_LIMIT_ENABLED') && RATE_LIMIT_ENABLED) {
            $this->rateLimiter = new RateLimiter(
                defined('RATE_LIMIT_REQUESTS') ? RATE_LIMIT_REQUESTS : 60
            );
            
            // Check rate limit before processing request
            $this->checkRateLimit();
        }
    }
    
    /** 
     * __call magic method. 
     */
    public function __call($name, $arguments)
    {
        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }
    
    /**
     * Check if rate limit is exceeded
     */
    protected function checkRateLimit()
    {
        if ($this->rateLimiter->isLimitExceeded()) {
            $remaining = $this->rateLimiter->getRemainingRequests();
            $resetTime = $this->rateLimiter->getResetTime();
            
            $this->sendOutput(
                json_encode(array('error' => 'Rate limit exceeded. Try again later.')),
                array(
                    'HTTP/1.1 429 Too Many Requests',
                    'Content-Type: application/json',
                    'X-RateLimit-Limit: ' . RATE_LIMIT_REQUESTS,
                    'X-RateLimit-Remaining: ' . $remaining,
                    'X-RateLimit-Reset: ' . $resetTime,
                    'Retry-After: ' . $resetTime
                )
            );
        }
    }
    
    /** 
     * Get URI elements. 
     * 
     * @return array 
     */
    protected function getUriSegments()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode( '/', $uri );
        return $uri;
    }
    
    /** 
     * Get querystring params. 
     * 
     * @return array 
     */
    protected function getQueryStringParams()
    {
        return parse_str($_SERVER['QUERY_STRING'], $query);
    }
    
    /** 
     * Send API output. 
     * 
     * @param mixed $data 
     * @param string $httpHeader 
     */
    protected function sendOutput($data, $httpHeaders=array())
    {
        header_remove('Set-Cookie');
        
        // Add rate limit headers if rate limiting is enabled
        if (isset($this->rateLimiter) && defined('RATE_LIMIT_ENABLED') && RATE_LIMIT_ENABLED) {
            $remaining = $this->rateLimiter->getRemainingRequests();
            $resetTime = $this->rateLimiter->getResetTime();
            
            $httpHeaders[] = 'X-RateLimit-Limit: ' . RATE_LIMIT_REQUESTS;
            $httpHeaders[] = 'X-RateLimit-Remaining: ' . $remaining;
            $httpHeaders[] = 'X-RateLimit-Reset: ' . $resetTime;
        }
        
        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }
        
        echo $data;
        exit;
    }
}
?>