# API Endpoint
Endereço: `/api/main.php`
## Métodos & Parâmetros
Único método aceitado: GET.
Parâmetros: `action || value || random`
# Tipos de action e values.
## Values:
**value**: Valor atribuído para a **action**.
**random**: 1 ou 0, determina se o resultado vai ser aleatório.

> **random** é somente utilizado em **get_recipes**

## Actions
**action: 'get_recipe'**
> Retorna a receita (**value**) ou **status == error**.
> **value** é o ID da receita a ser procurada.

**action: 'get_recipes'**
> Retorna **30** receitas (caso o **value** não seja especificado) ou **status == error**.
>
> **value** é a **quantidade** de receitas a ser retornada (não obrigatório) 
> **random** é se as receitas vão ser retornadas aleatoriamente

**action: 'search'**
> Retorna todos os items que contém em seu nome ou ingrediente o valor de **value**. Retorna **status == error** caso nenhuma receita tenha sido encontrada.
>
>**value** é o nome da receita/ingrediente.

# Retorno da API.
O valor é retornado como JSON pelo PHP mas é interpretado como OBJECT pelo jQuery.

    JSON 
    {
	    category: str,
	    id: int,
	    ingredients: list (array),
	    instructions: str,
	    name: str,
	    preptime: str,
	    thumb: str
    }

# Exemplos de requests com jQuery

## Obtendo receita por ID.

    $.get("/api/main.php", {
	    action: 'get_recipe',
	    value: 10
    }, function (data, status) {
	    if(data.status == 'error') {
		    // Exibir mensagem de error.
		    return false;
	    }
		console.log(data);
    });
   
   ## Obtendo todas as receitas de forma aleatória
   

    $.get("/api/main.php", {
	    action: 'get_recipes',
	    value: 1000,
	    random: 1
    }, function (data, status) {
	    if(data.status == 'error') {
		    // Exibir mensagem de error
		    return false;
	    }
		
		// Loop no retorno
		$.each(data, function (key, value) {
			console.log(key, value);
		})
	}

## Pesquisando por receita
	

    $.get("/api/main.php", {
	    action: 'get_recipe',
	    value: 'salada'
    }, function (data, status) {
	    if(data.status == 'error') {
		    // Exibir mensagem de error
		    return false;
	    }
	    console.log(data);
    }



