$(document).ready(function () {
    // Checking permission
    $.get("/api/main.php", {
        action: "check_permission"
    }, function (data, status) {
        if (data.status == "error") {
            alert("Você não tem permissão para usar esta página!");
            window.location.href = "/index.html";
            return false;
        }
    })

    $("#submitReceita").click(function () {
        var receitaNome = $("#receitaNome").val();
        var categoria   = $("#categoria").val();
        var instrucoes  = $("#instrucoes").val();
        var ingredientes = $("#ingredientes").val();
        var rendimento  = $("#rendimento").val();
        var imagem      = $("#imagem").val();
        var preparo     = $("#preparo").val();

        $.post("/api/main.php", {
            action: 'add_recipe',
            values: {
                name: receitaNome,
                category: categoria,
                instructions: instrucoes,
                ingredients: ingredientes,
                measures: rendimento,
                thumb: imagem,
                preptime: preparo
            }
        }, function (data, status) {
            console.log(data);
            if (data.status == "error") {
                alert(data.message);
                return false;
            }

            alert(data.message);
        })
    })
})