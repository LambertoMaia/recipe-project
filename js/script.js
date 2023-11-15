$(document).ready(function () {

    var lastSearch = "";

    $("#searchInput").on("keyup", function (e) {
        if (e.keyCode == 13) {
            searchMeal();
        }
    })

    $("#searchButton").click(searchMeal);

    function searchMeal() {
        var value = $("#searchInput").val();
        
        if (value == "" || value == null) {
            alert("Por favor, digite um termo de pesquisa!");
            //loadDefault();
            return false;
        }
        
        if (lastSearch == value) {
            return false;
        } else {
            lastSearch = value
        }

        // Limpando a lista de receitas.
        $("#mealList").html("");

        $.get("/api/main.php", {
            action: 'search',
            value: value
        }, function (data, status) {
            if (data.status == 'error') {
                alert("Não foi possível encontrar o termo pesquisado!");
                loadDefault();
                return false;
            }
            
            $.each(data, function (key, value) {
                const mealItem = document.createElement('div');
                mealItem.classList.add('meal-item');
                mealItem.dataset.id = value.id;
                mealItem.innerHTML = `
                <img src="${value.thumb}" alt="${value.name}">
                <h3>${value.name}</h3>
                `;
                mealList.appendChild(mealItem);
            })
        })
    }

    function loadDefault() {
        $("#mealList").html(""); 

        $.get("/api/main.php", {
            action: 'get_recipes',
            value: 12,
            random: 1
        }, function (data, status) {
            $.each(data, function (key, value) {
                // Carregando 12 receitas.
                const mealItem = document.createElement('div');
                mealItem.classList.add('meal-item');
                mealItem.dataset.id = value.id;
                mealItem.innerHTML = `
                <img src="${value.thumb}" alt="${value.name}">
                <h3>${value.name}</h3>
                `;
                mealList.appendChild(mealItem);
            })
        })
    }

    loadDefault();
})