$(document).ready(function () {

    function userlogin() {
        var username = $("#usernameField").val();
        var password = $("#passwordField").val();

        $.post("/api/main.php", {
            action: 'login',
            username: username,
            password: password
        }, function (data, status) {
            if (data.status == "error") {
                alert(data.message);
                return false;
            }

            window.location.href = "/adicionar.html";
        })
    }

    $("#usernameField, #passwordField").keypress(function (e) {
        if (e.which == 13) {
            userlogin();
        }
    })

    $("#submitButton").click(userlogin);
})