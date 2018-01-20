<?php

require_once __DIR__ . '/generic-views.php';

class ABACO_AdminCompanyTableController {
    public $data;
    public function __construct(ABACO_ParticipantDbTable $part_table) {
        $this->data = $part_table->query_all(
            ['id', 'nif', 'first_name', 'email'],
            'contact_participant_id IS NOT NULL');
    }
}

class ABACO_AdminCompanyTableView extends ABACO_AdminView {
    private $m_controller;
    public function __construct(ABACO_AdminCompanyTableController $controller) {
        $this->m_controller = $controller;
    }
    public function code() {
        $res = '<h1>' . esc_html__('Companies', 'abaco') . '</h1><p>' .
            sprintf(esc_html__('Total companies: %d'),
                count($this->m_controller->data)) . '</p>' .
            $this->company_table()->code();
        return self::wrap($res);
    }
    
    private function company_table() {
        $data = $this->m_controller->data;
        for ($i = 0; $i != count($data); ++$i) {
            $data[$i]['_details'] = new ABACO_AdminButtonLink(
                abaco_admin_participant_link($data[$i]['id']),
                __('See details', 'abaco')
            );
        }
        $headers = [
            'nif' => __('NIF', 'abaco'),
            'first_name' => __('Name', 'abaco'),
            'email' => __('Email', 'abaco'),
            '_details' => ''
        ];
        return new ABACO_AdminRowTable($headers, $data);
    }
}
