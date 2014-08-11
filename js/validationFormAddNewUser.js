jQuery(function($) {

  	$('button').click(function(e){
  		e.preventDefault();

	  	var form = $('form');
	  	var formData = {
	  		screen_name: form.find('input[name="screen_name"]').val(),
	  		password: form.find('input[name="password"]').val(),
  			costumer_key: form.find('input[name="costumer_key"]').val(),
  			costumer_secret: form.find('input[name="costumer_secret"]').val(),
  			access_token: form.find('input[name="access_token"]').val(),
  			access_token_secret: form.find('input[name="access_token_secret"]').val(),
	  	};

	  	$.ajax({
			type: "post",
			url: "http://twit-bot.loc/page/addAccount.php",
			data:{ formValidation: formData, ajax: 'ajax' } ,

			dataType : "json",
			success: function(responce){
				// если есть ошибки
				if( responce.error !== undefined ){

					// очищаем прежние ошибки
					clearInputErrorText();

					// выводим ошибки
					for(var elem in responce.error) {
					  	form.find('#' + elem ).text(responce.error[elem]).css({color: 'red'});
					}
				}
				
				// если все поля не пустые
				if( responce.success !== undefined ){

					clearInputErrorText();
					clearInputValue();
					$('#msg').text( responce.success ).css({fontSize: 'bold', color: 'blue'});
				}
  	    	},
  	    	beforeSend: function(){
  	    	    //создаем div
  	    	    var loading = $("<div>", {
  	    	      "class" : "loading"
  	    	    });
  	    	    //выравним div по центру формы
  	    	    $(loading).css("top", 120).css("left", 40);

  	    	    $('form').css({'opacity': '0.3'});
  	    	    //добавляем созданный div в конец документа
  	    	    $("body").append(loading);
  	    	  },
  	    	  complete: function() {
  	    	    //уничтожаем div
  	    	    $(".loading").detach();
  	    	    $('form').css({'opacity': '1'});
  	    	  }
  		});
	});

	function clearInputErrorText(){
		$('form input').each(function(){
			$('form').find( '#' + $(this).attr('name') ).text('');
		});
	}

	function clearInputValue(){
		$('form input').each(function(){
			$(this).val('');
		});
	}


});