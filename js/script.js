$(document).ready(function () {

    var lastSearch = "";

    $("#search-input").on("keyup", function (e) {
        if (e.keyCode == 13) {
            searchMeal();
        }
    })

    $("#search-button").click(searchMeal);

    function searchMeal() {
        var value = $("#search-input").val();
        console.log("value", value);
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
                mealItem.addEventListener('click', function() {
                    getMeal(value.id);
                });
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
                mealItem.addEventListener('click', function() {
                    getMeal(value.id);
                });
                mealList.appendChild(mealItem);
            })
        })
    }

    function getMeal(mealId) {
        $.get("/api/main.php", {
            action: 'get_recipe',
            value: Number(mealId)
        }, function (data, status) {
            if (data.status == "error") {
                alert(data.message);
                return false;
            }
    
            var mealDetailsContent = document.querySelector(".meal-details-content");
            var modalContainer = document.querySelector(".modal-container");
            var meal = data;
            
            console.log(data.ingredients);

            var ingredients = $("<div></div>");
            ingredients.addClass("ingredients");
            
            ingredients.prepend("<h3>Ingredientes:</h3>");

            var ingredients_list = $("<ul></ul>");
            ingredients_list.addClass("ingredients-list");

            $.each(data.ingredients, function (key, value) {
                ingredients_list.append("<li>" + value + "</li>");
                ingredients.append(ingredients_list);
            })

            var category = meal.category.replace(/-/g, " ");

            mealDetailsContent.innerHTML = `
                <h2 class="recipe-title">${meal.name}</h2>
                <p class="recipe-category">${category}</p>
                <div class="recipe-instruct">
                    <h3>Instruções:</h3>
                    <p>${meal.instructions}</p>
                </div>
                <div class="recipe-img">
                    <img src="${meal.thumb}" alt="${meal.name}">
                </div>
                <div class="recipe-video">
                    
                </div>
            `;
            modalContainer.style.display = 'block';

            $(".recipe-instruct").append(ingredients);

        });
        $("body").on("click", "#recipeCloseBtn", function () {
            document.querySelector(".modal-container").style.display = 'none';
        })
    }


    loadDefault();
})