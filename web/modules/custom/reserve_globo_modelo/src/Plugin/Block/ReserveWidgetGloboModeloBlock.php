<?php

namespace Drupal\reserve_globo_modelo\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "reserve_widget_globo_modelo_block",
 *   admin_label = @Translation("Reserve Widget Globo Modelo block"),
 *   category = @Translation("Reserve Widget Globo Modelo block")
 * )
 */
class ReserveWidgetGloboModeloBlock extends BlockBase {

  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\reserve_globo_modelo\Form\ReserveWidgetGloboModeloForm');
    return $form;
   }
}