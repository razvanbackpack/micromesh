
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$_ENV['APP_NAME']}} - {{$_ENV['APP_VERSION']}}</title>
</head>
<body>
    <section id = "main-body">
        <p>
            {{ $message }}
        </p>
    </section>  

    <section id = "footer">
        <footer>
        {{$_ENV['APP_NAME']}} - {{$_ENV['APP_VERSION']}}
        </footer>
    </section>
</body>
</html>