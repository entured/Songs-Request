jQuery(document).ready(function($) {
  // Manipulación del formulario de solicitud
  $('.song-request-form').submit(function(e) {
    e.preventDefault(); // Evitar el envío del formulario por defecto
    
    // Obtener los valores del formulario
    var artist = $(this).find('input[name="artist"]').val();
    var title = $(this).find('input[name="title"]').val();
    var message = $(this).find('textarea[name="message"]').val();
    
    // Realizar la solicitud al servidor
    $.ajax({
      url: ajaxurl, // URL de la acción AJAX de WordPress
      type: 'POST',
      data: {
        action: 'song_request_submit',
        artist: artist,
        title: title,
        message: message
      },
      success: function(response) {
        // Mostrar el mensaje de éxito o error
        alert(response);
      },
      error: function(xhr, status, error) {
        // Manejar el error de la solicitud
        console.log(xhr.responseText);
      }
    });
  });
});
