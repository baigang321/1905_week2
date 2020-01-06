<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<table border="2">
    <tr>
        <td>ID</td>
        <td>姓名</td>
        <td>邮箱</td>
        <td>时间</td>
        <td>操作</td>
    </tr>
    @foreach ($data as $v)
        <tr>
            <td>{{$v->uid}}</td>
            <td>{{$v->user_name}}</td>
            <td>{{$v->email}}</td>
            <td>{{$v->created_at}}</td>
            <td><a href="{{url('test/delete/'.$v->uid)}}">删除</a>||<a href="{{url('test/update/'.$v->uid)}}">修改</a></td>
            @endforeach
        </tr>

</table>
</body>
</html>