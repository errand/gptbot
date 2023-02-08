<?php
/**
Simple Chat example
 **/
namespace Kottenberg;

class Chatgpt
{

    public function __construct($apiKey, $prompt)
    {
        $this->apiKey = $apiKey;
        $this->prompt = $prompt;
    }

    // The URL for OpenAI's API endpoint
    public $url = "https://api.openai.com/v1/completions";

    // Function to generate a response from OpenAI's API
    public function generateResponse()
    {
        // The data for the API request
        $data = [
            "model" => "text-davinci-002",
            "prompt" => $this->prompt,
            "temperature" => 0.7,
            "max_tokens" => 256,
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0
        ];

        // Use curl to send a post request to the OpenAI API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        // Decode the JSON response from the API
        $responseData = json_decode($response, true);
        // Extract the generated text from the response
        $generatedText = $responseData["choices"][0]["text"];

        return $generatedText;
    }
}
