(function($){
	$(document).ready(function() {

		if(!database.is_admin) {
			jQuery(".telefone input")
	        .mask("(99) 9999-9999?9")
	        .focusout(function (event) {  
	            var target, phone, element;  
	            target = (event.currentTarget) ? event.currentTarget : event.srcElement;  
	            phone = target.value.replace(/\D/g, '');
	            element = $(target);  
	            element.unmask();  
	            if(phone.length > 10) {  
	                element.mask("(99) 99999-999?9");  
	            } else {  
	                element.mask("(99) 9999-9999?9");  
	            }  
	        });
    	}

			$(".rua input[type='text']").attr("readonly", "readonly");
			$(".rua input[type='text']").addClass("disabled");

            $(".bairro input[type='text']").attr("readonly", "readonly");
            $(".bairro input[type='text']").addClass("disabled");

            $(".cidade input[type='text']").attr("readonly", "readonly");
            $(".cidade input[type='text']").addClass("disabled");

            $(".estado input[type='text']").attr("readonly", "readonly");
            $(".estado input[type='text']").addClass("disabled");


            function limpa_formulário_cep() {
                // Limpa valores do formulário de cep.
                $(".rua input[type='text']").val("");
                $(".bairro input[type='text']").val("");
                $(".cidade input[type='text']").val("");
                $(".estado input[type='text']").val("");
            }
            
            //Quando o campo cep perde o foco.
            $(".btn-search-cep").click(function(e) {

                //Nova variável "cep" somente com dígitos.
                var cep = $(".cep input[type='text']").val().replace(/\D/g, '');

                //Verifica se campo cep possui valor informado.
                if (cep != "") {

                    //Expressão regular para validar o CEP.
                    var validacep = /^[0-9]{8}$/;

                    //Valida o formato do CEP.
                    if(validacep.test(cep)) {

                        //Preenche os campos com "..." enquanto consulta webservice.
                        $(".rua input[type='text']").val("Procurando endereço...")
                        $(".bairro input[type='text']").val("Procurando endereço...")
                        $(".cidade input[type='text']").val("Procurando endereço...")
                        $(".estado input[type='text']").val("Procurando endereço...")

                        //Consulta o webservice viacep.com.br/
                        $.getJSON("//viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                            if (!("erro" in dados)) {
                                //Atualiza os campos com os valores da consulta.
                                $(".rua input[type='text']").val(dados.logradouro);
                                $(".bairro input[type='text']").val(dados.bairro);
                                $(".cidade input[type='text']").val(dados.localidade);
                                $(".estado input[type='text']").val(dados.uf);
                            } //end if.
                            else {
                                //CEP pesquisado não foi encontrado.
                                limpa_formulário_cep();
                                alert("CEP não encontrado.");
                            }
                        });
                    } //end if.
                    else {
                        //cep é inválido.
                        limpa_formulário_cep();
                        alert("Formato de CEP inválido.");
                    }
                } //end if.
                else {
                    //cep sem valor, limpa formulário.
                    limpa_formulário_cep();
                    alert("Digite seu CEP.");

                }
                e.preventDefault();
            });
        });
})(jQuery);