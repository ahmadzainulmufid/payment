<?php

// Konfigurasi Server Key Midtrans
define('SERVER_KEY', '');
define('MIDTRANS_API_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions');

// Fungsi untuk membuat Snap Token
function createSnapToken($order_id, $gross_amount, $customer_details) {
    $payload = [
        'transaction_details' => [
            'order_id' => $order_id,
            'gross_amount' => $gross_amount
        ],
        'customer_details' => $customer_details,
        'credit_card' => [
            'secure' => true
        ]
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => MIDTRANS_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(SERVER_KEY)
        ]
    ]);

    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_status == 201) {
        return json_decode($response, true);
    } else {
        throw new Exception("Midtrans API Error: " . $response);
    }
}

// Endpoint untuk menerima request dari aplikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        // Ambil data dari body request
        $input = json_decode(file_get_contents('php://input'), true);

        // Pastikan data sesuai
        $order_id = $input['order_id'];
        $gross_amount = $input['gross_amount'];
        $customer_details = $input['customer_details'];

        // Panggil fungsi untuk membuat Snap Token
        $snapTokenResponse = createSnapToken($order_id, $gross_amount, $customer_details);

        // Kirim response Snap Token ke frontend
        echo json_encode([
            'status' => 'success',
            'token' => $snapTokenResponse['token'],
            'redirect_url' => $snapTokenResponse['redirect_url']
        ]);
    } catch (Exception $e) {
        // Kirim error jika terjadi masalah
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    // Jika bukan POST, kirim pesan error
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
