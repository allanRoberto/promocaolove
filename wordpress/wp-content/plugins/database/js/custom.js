(function($){
	$(document).ready(function(){
		$('.btn-search-cep').click(function(event){

			cep = $('.search-cep input[type="text"]').val();

			if(cep == " ") {
				$(this).next('.msg-cep').html('Por favor digite seu CEP.');
			}
			else  {
				$.ajax({
					url        : database.ajaxurl,
					dataType   : 'json',
					method     : 'POST',
					data       : { action: 'search_cep_ajax', cep : cep},
					beforeSend : function(){
						$(this).next('.msg-cep').html('Buscando endereço, por favor aguarde ...');
					}
				}).done(function(data){

				});
			}
			event.preventDefault();	
		});
	});
})(jQuery);