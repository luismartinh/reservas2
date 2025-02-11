<?php

namespace app\traits;



trait FormTrait
{


  public function init($view)
  {

    $initScript3 = <<<JS2
        var GL_changed=false;     
        var GL_submit=false;         
        
        
        function mostrarModalEspera(){
          var modalEspera = new bootstrap.Modal(document.getElementById('modalEspera'));
          modalEspera.show(); // Muestra el modal
        }
     JS2;
    $view->registerJs($initScript3, \yii\web\View::POS_BEGIN);




    $initScript4 = <<<JS1
    
   $('form :input').change(function(){GL_changed=true;});
        
    $(window).on('beforeunload', function() {
        if(GL_changed && !GL_submit)  return 'Leave page?';
    });        

    $('form').keyup(function(e) {
      return e.which !== 13  
    });        
        
    $(document).ready(function() {
      $(window).keydown(function(event){
        if(event.keyCode == 13 && !$(document.activeElement).is('textarea')) {
          event.preventDefault();
          return false;
        }
      });


        
      $('form').on('beforeSubmit', function() {
            GL_submit=true;
            mostrarModalEspera();

        });
        


    });
        
        
JS1;
    $view->registerJs($initScript4, \yii\web\View::POS_READY);



  }


  public function getModal()
  {

    $modalhtml = <<<JS
    <!-- MODAL DE ESPERA -->
    <div class="modal fade" id="modalEspera" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h5 class="mt-3">Espere un momento...</h5>
            </div>
        </div>
    </div>
    JS;

    return $modalhtml;

  }

}


