<?php

namespace Drupal\reserve_globo_modelo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\reserve_globo_modelo\ReserveGloboModeloManagerInterface;

/**
 * Declaration of class ReserveCheckGloboModeloForm.
 */
class ReserveCheckGloboModeloForm extends FormBase {

  protected $commonFunction;
  public $types_flights;
  public $states_flights;

  public function __construct(ReserveGloboModeloManagerInterface $interface) {
    $this->commonFunction = $interface;
    $this->types_flights = $this->commonFunction->typesFlights();
    $this->states_flights = $this->commonFunction->statesFlights();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('reserve_globo_modelo.manager')
    );
  }

  public function getFormId() {
    return 'reserve_check_globo_modelo_form';
  }

  protected function getEditableConfigNames() {
    return [
      'reserve_globo_modelo.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;  

    $form['container'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax-response-container'],
        '#prefix' => '<div class="reserve-check">',
        '#suffix' => '</div>'
    ];
    $form['container']['html'] = [
      '#type' => 'markup',
      '#markup' => '<div class="title"> Ingresa el código de reserva </div>',
    ];
    $form['container']['query'] = [
      '#type' => 'textfield',
      '#placeholder' => 'Buscar',
      '#default_value' => '',
      '#required' => TRUE,
      '#prefix' => '<div class="query">',
      '#suffix' => '<span class="query-valid-message"></span></div>'
    ];
    $form['container']['actions']['#type'] = 'actions';
    $form['container']['actions']['submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('Consultar'),
      '#ajax' => [
        'callback' => '::setMessage'
      ],
    ];

    $form_state->setCached(FALSE);
    return $form;
  }


  public function setMessage(array $form, FormStateInterface $form_state) {    
    $response = new AjaxResponse();

    $query = $form_state->getValue('container')['query'];
    if (trim($query) == '') {
      $validate = false;
      $response->addCommand(new HtmlCommand('.query-valid-message', 'Ingresa el código de reserva'));
      $response->addCommand(new InvokeCommand('.query-valid-message', 'css', ['color', 'red']));
    }else {
      $validate = true;
      $response->addCommand(new ReplaceCommand('.query-valid-message', null));
    }
    
    if($validate) {
      $webform_id = 'reserve_globo_modelo';
      $results = $this->commonFunction->queryDataWebForm($webform_id, $query);
      $html = '<div class="message-response">';
      if(!empty($results)){
        foreach ($results as $key => $value) {
          switch ($value['state']) {
            case 'p':/*En confirmación*/
              $html = '
              <div class="state">Estado de la reserva: '.$this->states_flights[$value['state']].'</div>
              <div class="info">Estamos procesando la solicitud de tu reserva, pronto enviaremos la información al correo registrado</div>
              <div class="block-contact-info">
                <div class="message">
                  ¿Tienes alguna duda sobre tu reserva?<br>
                  Escribenos: 4642-8949
                </div>
                <div class="image">
                  <img src="/modules/custom/reserve_globo_modelo/img/whatsapp.png" width="80px" height="80px">
                </div>
              </div>';
              break;
            case 'o':/*Confirmada*/
              $html = '
                <div class="title">Codigo de reserva: '.$value['id_reserve'].'</div><br>
                <div class="state">Estado de la reserva: '.$this->states_flights[$value['state']].'</div><br>
                <div class="info">
                  '.$$value['date'].'<br>
                  Direccion: XXXXXXX <br>
                  Tipo de vuelo: '.$this->types_flights[$value['type']].'<br>
                  Número de participantes: '.$value['participants'].'<br><br><br>
                </div>';
              break;
            case 'c':/*Cancelada*/
              $html = '
              <div class="state">Estado de la reserva: '.$this->states_flights[$value['state']].'</div>
              <div class="block-contact-info">
                <div class="message">
                  ¿Tienes alguna duda sobre tu reserva?<br>
                  Escribenos: 4642-8949
                </div>
                <div class="image">
                  <img src="/modules/custom/reserve_globo_modelo/img/whatsapp.png" width="80px" height="80px">
                </div>
              </div>';
              break;
          }
        }

        if($value['state'] === 'o'){
          $webform_id = 'reserve_data_globo_modelo';
          $results = $this->commonFunction->queryDataWebForm($webform_id, $query);
          foreach ($results as $key => $value) {
            $part = $key+1;
            $label = 'Datos '.$part.' Participante';
            $html = $html.'<div class="data">
              <div class="data-header">'.$label.'</div>
              Nombres: '.$value['full_name'].' <br>
              Fecha de nacimiento: '.$value['birthday'].' <br>
              DPI: '.$value['dpi'].' <br>
              Peso: '.$value['weight'].' <br>
              Genero: '.$value['gender'].'
            </div>';
          }
          
        }
        $html = $html.'<a href="/reservas/consulta" class="button">Regresar</a></div>';
        $response->addCommand(new HtmlCommand('#ajax-response-container', $html));
      }else {
        $response->addCommand(new HtmlCommand('.query-valid-message', 'Lo sentimos, No tenemos Resultados para tu búsqueda'));
      }
    }

    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
   //return parent::submitForm($form, $form_state);
  }
}
