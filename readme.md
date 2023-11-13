# API Endpoint
/api/main.php

Metodos: GET.
Parametros: action (Ação a ser executada) -- value (Valor atribuido a ação) -- random (Parametro especial para o get_recipes)

Actions: 
    get_recipe -> Value (int id)
        Retorna a receita ou status => error
        Value é o ID da receita a ser obtido (Obrigatório)
    
    get_recipes -> Value (int quantidade) || random (1 ou 0)
        Retorna 30 receitas (Por ordem de ID crescente) se o Value não for especificado
        Value é a quantidade de receitas para serem retornadas
        Se o random estiver com valor 1 então ele vai retornar a quantidade especificada (ou 30) de receitas aleatorias.
    
    search -> Value (string name)
        Retorna todas receitas que contém em seu nome ou igrediente o Value
        Value é o nome ou ingrediente da receita (Value obrigatório)
        Retorna status => error caso não seja encontrado ou Value não espeficiado

# Exemplo de requests com jQuery

1. Obtendo uma receita por ID. 
    $.get("/api/main.php", {
        action: 'get_recipe',
        value: 10
    }, function (data, status) {
        if(data.status == 'error') {
            // Error, exibir mensagem de error
            return false
        }

        console.log(data);
    })

2. Obtendo todas receitas
    $.get("/api/main.php", {
        action: 'get_recipes',
        value: 1000,
        random: 1 // Vai obter 1000 receitas em ordem aleatoria
    }, function (data, status) {
        console.log(data);
    })

3. Pesquisando por receita
    $.get("/api/main.php", {
        action: 'search',
        value: 'frango'
    }, function (data, status) {
        if(data.status == 'error') {
            return false;
        }

        // Loop pelos dados que retornou
        $.each(data, function (key, value) {
            console.log(key, value);
        })
    })

# Retorno da API
    Retorna JSON mas jquery entende como objeto
    JSON ->
        {
            category: str,
            id: int,
            ingredients: list (array),
            instructions: str,
            name: str,
            preptime: str,
            thumb: str
        }