$(document).ready(function () {
    function loadDefault() {
        $.get("/api/recipes/load", {}, function (data, status) {
            $('#mealList').html("");

            $.each(data, function (key, value) {
                $("#mealList").append(
                    `<div class='meal-item' dataset-id='${value.id}'>` +
                    `<img src='${value.thumb}' alt='${value.name}'>` +
                    `<h3>${value.name}</h3>` +
                    `</div>`
                );
            });
        });
    }

    var lastSearch = "";

    $("#search-input").on("keyup", function (e) {
        if (e.keyCode == 13) {
            searchMeal();
        }

        if ($(this).val() == "" && lastSearch != "") {
            loadDefault();
        }
    })

    $("#search-button").click(searchMeal);

    function searchMeal() {
        var value = $("#search-input").val();

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

        $.get("/api/recipes/search/" + value, {
        }, function (data, status) {
            if (data.status == 'error') {
                alert('Não foi possível encontrar receitas com o termo de pesquisa "' + value + '"');
                window.location.href = '/';
                return false;
            }
            
            $.each(data, function (key, value) {
                $("#mealList").append(
                    `<div class='meal-item' dataset-id='${value.id}'>` +
                    `<img src='${value.thumb}' alt='${value.name}'>` +
                    `<h3>${value.name}</h3>` +
                    `</div>`
                );
            });
        })
    }

    $('body').on('click', '.meal-item', function (e) {
        var mealId = Number($(e.currentTarget).attr("dataset-id"));
        var mealDetailsContent = document.querySelector(".meal-details-content");
        var modalContainer = document.querySelector(".modal-container");

        $.get("/api/recipe/" + mealId, {}, function (data, status) {
            var ingredients = $("<div></div>");
            ingredients.addClass("ingredients");

            ingredients.prepend("<h3>Ingredientes</h3>");

            var ingredients_list = $("<ul></ul>");
            ingredients_list.addClass("ingredients-list");

            $.each(data.ingredients, function (key, value) {
                ingredients_list.append("<li>" + value + "</li>");
                ingredients.append(ingredients_list);
            });

            var category = data.category.replace(/-/g, " ");

            $(".meal-details-content").html(
                `<h2 class='recipe-title'>${data.name}</h2>` +
                `<p class='recipe-category'>${category}</p>` +
                `<div class='recipe-instruct'>` +
                    `<h3>Instruções</h3>` +
                    `<p>${data.instructions}</p>` +
                `</div>` +
                `<div class='favourite'>` +
                    `<div class='fav-ico' dataset-id='${data.id}'>` +
                        `<img src='/templates/images/fav.png' alt=''>` +
                    `</div>` +
                    `<h3 class='center'>Adicionar como favorita</h3>` +
                `</div>` +
                `<div class='recipe-img'>` +
                    `<img src='${data.thumb}' alt='${data.name}'>` +
                `</div>` +
                `<div class='recipe-rate'>` +
                    `<h3>Avalie esta receita</h3>` +
                    `<div class='star-section' dataset-id='${data.id}'>` +
                        `<div class='star-container' dataset-value='1'></div>` +
                        `<div class='star-container' dataset-value='2'></div>` +
                        `<div class='star-container' dataset-value='3'></div>` +
                        `<div class='star-container' dataset-value='4'></div>` +
                        `<div class='star-container' dataset-value='5'></div>` +
                    `</div>` +
                `</div>` +
                `<div class='comments'>` +
                `</div>` 
            );
            
            $(".recipe-instruct").append(ingredients);
            modalContainer.style.display = "block";

            $("body").on("click", "#recipeCloseBtn", function () {
                document.querySelector(".modal-container").style.display = 'none';
            })

            $("body").on("mouseover", ".star-container", function (e) {
                var value = $(e.currentTarget).attr("dataset-value");

                $(".star-container").each(function (index, element) {
                    if (index < value) {
                        $(element).css("background-image", "url('/templates/images/star_hover.png");
                    } else {
                        $(element).css("background-image", "url('/templates/images/star.png");
                    }
                })
            })

            $("body").on("mouseleave", ".star-container", function (e) {
                $(".star-container").each(function (index, element) {
                    $(element).css("background-image", "url('/templates/images/star.png");
                })
            })

            $("body").on("click", ".star-container", function (e) {
                var value = $(e.currentTarget).attr("dataset-value");
                var id    = $(e.currentTarget).parent().attr("dataset-id");

                $.post("/avaliar", {
                    recipe_id: Number(id),
                    rating: Number(value)
                }, function (data, status) {
                    if (data.status == "error") {
                        alert(data.message);
                        return false;
                    }

                    alert(data.message);
                })
            })

            $("body").on("click", ".fav-ico", function (e) {
                var id = $(e.currentTarget).attr("dataset-id");

                $.post("/favoritar", {
                    recipe_id: Number(id)
                }, function (data, status) {
                    if (data.status == "error") {
                        alert(data.message);
                        return false;
                    }

                    alert(data.message);
                })
            })
        });
    });

});