<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 - Bad Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <div class="text-6xl font-bold text-red-600">400</div>
        <h1 class="mt-4 text-2xl font-semibold text-gray-800">Bad Request</h1>
        <p class="mt-2 text-gray-600">Permintaan tidak valid. Silakan periksa kembali input Anda.</p>
        <a href="{{ url()->previous() }}" class="mt-6 inline-block text-blue-600 hover:underline">Kembali</a>
        <a href="{{ route('dashboard') }}" class="ml-4 inline-block text-blue-600 hover:underline">Ke Dashboard</a>
    </div>
</body>
</html>