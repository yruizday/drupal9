<?php

namespace Drupal\reserve_globo_modelo\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "reserve_calendar_globo_modelo_block",
 *   admin_label = @Translation("Reserve Calendar Globo Modelo block"),
 *   category = @Translation("Reserve Calendar Globo Modelo block")
 * )
 */
class ReserveCalendarGloboModeloBlock extends BlockBase {

  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\reserve_globo_modelo\Form\ReserveCalendarGloboModeloForm');
    return $form;
   }
}