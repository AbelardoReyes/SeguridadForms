<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h1>Bienvenido, {{$name}}!</h1>
    <p>Gracias por registrarte con tu correo {{$email}}</p>
    <h3>Confirmacion</h3>
    <p>Para validar tu correo da click en el siguiente boton<br>
        Seras direccionado a un vista donde debes ingresar un codigo</p>
        <p>El codigo se enviara en otro correo</p>
    <button style="font-size:larger ;"><a href="{{$url}}">Validar</a></button>
</body>

</html>
