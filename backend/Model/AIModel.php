<?php
/**
 * AIModel
 *  - wraps all Gemini interactions
 *  - provides high-level helpers: reformatNote(), rewriteNote()
 */
include_once __DIR__ . '/../api_key.php';     // loads $geminiApiKey
class AIModel extends Database
{
    /** @var string */
    private $apiKey;

    public function __construct(?string $apiKey = null)
    {
        parent::__construct();
        if ($apiKey) {
            $this->apiKey = $apiKey;
        } else {
            global $geminiApiKey;
            if (empty($geminiApiKey)) {
                throw new \Exception("Gemini API key not configured");
            }
            $this->apiKey = $geminiApiKey;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  public high-level methods                                         */
    /* ------------------------------------------------------------------ */

    /** Summarise a note in one paragraph */
    public function reformatNote(string $username, int $note_id): bool
    {
        $note = $this->getNoteText($username, $note_id);

        $prompt = "You are a study assistant that produces concise, well-"
                . "structured summaries. Summarise the following note:\n\n"
                . $note;

        $summary = $this->callGemini($prompt, 0.4, 800);

        return $this->updateNote($username, $note_id, $summary);
    }

    /**
     * Rewrite a note in a chosen style: bullet | simplify | expand
     */
    public function rewriteNote(
        string $username,
        int    $note_id,
        string $style = 'bullet'
    ): bool {

        $note = $this->getNoteText($username, $note_id);

        switch ($style) {
            case 'simplify':
                $prompt = "Rewrite the following note in plain language "
                        . "so a 12-year-old can understand:\n\n" . $note;
                break;
            case 'expand':
                $prompt = "Expand the following note with extra detail and "
                        . "examples, keeping an academic tone:\n\n" . $note;
                break;
            case 'bullet':
            default:
                $prompt = "Rewrite the following note as concise bullet points:"
                        . "\n\n" . $note;
        }

        $rewritten = $this->callGemini($prompt, 0.4, 1000);

        return $this->updateNote($username, $note_id, $rewritten);
    }

    /* ------------------------------------------------------------------ */
    /*  PRIVATE HELPERS                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Centralised Gemini call
     *
     * @throws \Exception on HTTP or JSON error
     */
    private function callGemini(
        string  $prompt,
        float   $temperature = 0.4,
        int     $maxTokens   = 1000
    ): string {

        $endpoint = 'https://generativelanguage.googleapis.com/v1/'
                  . 'models/gemini-1.5-flash:generateContent';
        $url      = $endpoint . '?key=' . $this->apiKey;

        $payload = [
            'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]],
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => $maxTokens
            ],
            'safetySettings' => [[
                'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ]]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15
        ]);

        $raw = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception("Gemini cURL error: " . curl_error($ch));
        }
        curl_close($ch);

        $json = json_decode($raw, true);
        if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception("Gemini response malformed: " . $raw);
        }

        return trim($json['candidates'][0]['content']['parts'][0]['text']);
    }

    /** fetch note text or throw */
    private function getNoteText(string $user, int $id): string
    {
        $stmt = $this->connection->prepare(
            "SELECT note FROM notes WHERE id=? AND username=?"
        );
        $stmt->bind_param("is", $id, $user);
        $stmt->execute();
        $stmt->bind_result($note);
        if (!$stmt->fetch()) {
            $stmt->close();
            throw new \Exception("Note not found or permission denied.");
        }
        $stmt->close();
        return $note;
    }

    /** update note text */
    private function updateNote(string $user, int $id, string $newText): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE notes SET note=? WHERE id=? AND username=?"
        );
        $stmt->bind_param("sis", $newText, $id, $user);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new \Exception("Failed to update note: $err");
        }
        $stmt->close();
        return true;
    }
}
