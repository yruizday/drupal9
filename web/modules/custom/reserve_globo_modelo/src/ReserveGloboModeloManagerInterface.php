<?php

namespace Drupal\reserve_globo_modelo;

interface ReserveGloboModeloManagerInterface {
	public function typesFlights();
	public function statesFlights();
	public function daysList();
	public function monthsList();
	public function yearsList();
	public function citysList();
	public function weightsList();
	public function gendersList();
	public function saveDataWebForm($webform_id, $data);
	public function queryDataWebForm($webform_id, $query);
	public function queryAvailabilityFlightsWebForm($webform_id, $date, $participants);
	public function generateToken($strength);
	public function purchaseSummary($flight, $date, $participants);
	public function calculatePrice($participants, $flight);
}
