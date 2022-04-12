<?php

namespace Drupal\reserve_globo_modelo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Declaration of class ReserveWidgetGloboModeloForm.
 */
class ReserveWidgetGloboModeloForm extends FormBase {

  public function getFormId() {
    return 'reserve_widget_globo_modelo_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['flight'] =[
      '#type' => 'radios',
      '#title' => t('Selecciona el Tipo de Vuelo'),
      '#options' => ['c' => 'Vuelo Cautivo', 'l' => 'Vuelo Libre'],
      '#required' => TRUE,
      '#prefix' => '<div class="flight">',
      '#suffix' => '</div>',
    ];
    for ($i=1; $i < 9; $i++) { 
      $p[$i] = $i;
    }
    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Selecciona la Fecha y Visitantes'),
      '#required' => TRUE,
      '#prefix' => '<div class="date">',
      '#suffix' => '</div>',
    ];
    $form['participants'] = [
      '#type' => 'select',
      '#options' => $p,
      '#default_value' => '1',
      '#required' => TRUE,
      '#prefix' => '<div class="participants">',
      '#suffix' => '</div>',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('Reserva aquí'),
    ];
    $form_state->setCached(FALSE);
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $flight = $form_state->getValue('flight');
    if (trim($flight) == '') {
      $form_state->setErrorByName('flight', $this->t('Debes seleccionar una opción'));
    }
    $date = $form_state->getValue('date');
    if (trim($date) == '') {
      $form_state->setErrorByName('date', $this->t('Debes seleccionar una fecha'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $redirect_params['f'] = $form_state->getValue('flight');
    $redirect_params['d'] = $form_state->getValue('date');
    $redirect_params['p'] = $form_state->getValue('participants');
    $url = Url::fromRoute('reserve_globo_modelo.reserve', $redirect_params);
    $form_state->setRedirectUrl($url);
    //return parent::submitForm($form, $form_state);
  }
}
