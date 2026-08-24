<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/ml.php';

final class MlPredictionException extends RuntimeException
{
    public function __construct(
        private readonly string $browserMessage,
        string $technicalMessage
    ) {
        parent::__construct($technicalMessage);
    }

    public function browserMessage(): string
    {
        return $this->browserMessage;
    }
}

function expected_quality_label(int $quality): string
{
    return match (true) {
        $quality >= 7 => 'Excellent',
        $quality >= 6 => 'Good',
        $quality >= 5 => 'Average',
        default => 'Poor',
    };
}

/**
 * @param array<string, float> $values
 * @return array{
 *     predicted_quality: int,
 *     quality_label: string,
 *     model: array{name: string, algorithm: string, version: string}
 * }
 */
function request_model_prediction(array $values): array
{
    if (!function_exists('curl_init')) {
        throw new MlPredictionException(
            'The prediction service is unavailable. Enable PHP cURL and try again.',
            'PHP cURL extension is not enabled.'
        );
    }

    try {
        $requestBody = json_encode($values, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new MlPredictionException(
            'The prediction request could not be prepared.',
            'Prediction JSON encoding failed: ' . $exception->getMessage()
        );
    }

    $handle = curl_init(ML_API_PREDICT_URL);
    if ($handle === false) {
        throw new MlPredictionException(
            'The prediction service is unavailable. Start the Python model API and try again.',
            'Unable to initialize a cURL handle for the prediction API.'
        );
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => $requestBody,
        CURLOPT_CONNECTTIMEOUT_MS => ML_API_CONNECT_TIMEOUT_MS,
        CURLOPT_TIMEOUT_MS => ML_API_TIMEOUT_MS,
        CURLOPT_NOSIGNAL => true,
    ]);

    $responseBody = curl_exec($handle);
    $curlError = curl_error($handle);
    $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);

    if ($responseBody === false) {
        throw new MlPredictionException(
            'The prediction service is unavailable. Start the Python model API and try again.',
            'Prediction API request failed: ' . $curlError
        );
    }

    try {
        $response = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new MlPredictionException(
            'The prediction service returned an invalid response.',
            'Prediction API JSON decoding failed: ' . $exception->getMessage()
        );
    }

    if (!is_array($response)) {
        throw new MlPredictionException(
            'The prediction service returned an invalid response.',
            'Prediction API response was not a JSON object.'
        );
    }

    if ($statusCode === 400) {
        $apiMessage = $response['error']['message'] ?? 'One or more values are invalid.';
        if (!is_string($apiMessage) || $apiMessage === '') {
            $apiMessage = 'One or more values are invalid.';
        }
        throw new MlPredictionException(
            'The model rejected the submitted values: ' . $apiMessage,
            'Prediction API rejected input with HTTP 400.'
        );
    }

    if ($statusCode !== 200) {
        throw new MlPredictionException(
            'The prediction service is unavailable. Start the Python model API and try again.',
            'Prediction API returned HTTP ' . $statusCode . '.'
        );
    }

    $quality = $response['predicted_quality'] ?? null;
    $label = $response['quality_label'] ?? null;
    $model = $response['model'] ?? null;

    if (
        !is_int($quality)
        || $quality < 3
        || $quality > 8
        || !is_string($label)
        || $label !== expected_quality_label($quality)
        || !is_array($model)
        || !is_string($model['name'] ?? null)
        || $model['name'] === ''
        || !is_string($model['algorithm'] ?? null)
        || $model['algorithm'] === ''
        || !is_string($model['version'] ?? null)
        || $model['version'] === ''
    ) {
        throw new MlPredictionException(
            'The prediction service returned an invalid response.',
            'Prediction API response failed schema validation.'
        );
    }

    return [
        'predicted_quality' => $quality,
        'quality_label' => $label,
        'model' => [
            'name' => $model['name'],
            'algorithm' => $model['algorithm'],
            'version' => $model['version'],
        ],
    ];
}
