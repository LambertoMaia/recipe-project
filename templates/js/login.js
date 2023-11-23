$(document).ready(function () {
    function userlogin() {
        var username = $("#usernameField").val();
        var password = $("#passwordField").val();

        if (username == "" || password == "") {
            alert("Preencha todos os campos");
            return false;
        }

        $.post("/login", {
            username: username,
            password: password
        }, function (data) {
            console.log(data.status);
            if (data.status == "ok") {
                window.location.href = "/";
            } else {
                alert("Usuário ou senha incorretos");
            }
        });
    }

    function userRegister() {
        var username = $("#usernameField").val();
        var password = $("#passwordField").val();
        var email    = $("#emailField").val();

        if (username == "" || password == "" || email == "") {
            alert("Preencha todos os campos");
            return false;
        }

        $.post("/cadastro", {
            username: username,
            password: password,
            email: email
        }, function (data) {
            if (data.status == "ok") {
                alert("Usuário cadastrado, efetue o login");
                window.location.href = "/login";
            } else {
                alert(data.message);
            }
        })
    }

    $("#usernameField, #passwordField").keypress(function (e) {
        if (e.which == 13) {
            userlogin();
        }
    })

    $("#submitButton").click(userlogin);
    $("#registrationSubmit").click(userRegister);
})