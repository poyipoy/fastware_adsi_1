<!DOCTYPE html>
<html>

<head>
    <title>Convert JSON to CSV</title>
</head>

<body>
    <h1>Convert JSON to CSV</h1>
    <form action="{{ route('convert.json') }}" method="POST">
        @csrf
        <textarea name="json_text" rows="30" cols="220" placeholder="Paste your JSON here..." required></textarea><br>
        <button type="submit">Convert to CSV</button>
    </form>

    @if (session('error'))
        <div style="color: red;">{{ session('error') }}</div>
    @endif
</body>

</html>
