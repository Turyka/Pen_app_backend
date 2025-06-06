<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>App Usage Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="mb-4">App Usage Dashboard</h1>

        <table class="table table-bordered table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Device ID</th>
                    <th>Device Type</th>
                    <th>OS Version</th>
                    <th>IP</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($devices as $device)
                    <tr>
                        <td>{{ $device->id }}</td>
                        <td>{{ $device->device_id }}</td>
                        <td>{{ $device->device_type }}</td>
                        <td>{{ $device->os_version }}</td>
                        <td>{{ $device->ip }}</td>
                        <td>{{ $device->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
