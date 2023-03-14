<?php

namespace XD\Dashboard\Admin;

use SilverStripe\Security\Permission;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use XD\Dashboard\Model\Dashboard;
use XD\Dashboard\Model\Panel;

class DashboardAdmin extends ModelAdmin
{
    private static $url_segment = 'dashboard';

    private static $menu_title = 'Dashboard';
    
    private static $menu_priority = 1000;
    
    private static $menu_icon_class = 'font-icon-dashboard';

    private static $allowed_actions = [
        'addPanel'
    ];

    private static $managed_models = [
        Panel::class => [
            'title' => 'Dashboard'
        ],
        Dashboard::class => [
            'title' => 'Dashboards'
        ]
    ];

    public function init()
    {
        parent::init();
        Requirements::javascript('xddesigners/dashboard:client/dist/js/app.js');
        Requirements::css('xddesigners/dashboard:client/dist/styles/app.css');
        $this->extend('updateInit');
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if ($this->modelTab === Panel::class) {
            $form->setTemplate($this->getTemplatesWithSuffix('_Dashboard'));
            $form->Dashboard = $this->getDashboard();
        }
        return $form;
    }

    public function getDashboards()
    {
        return Dashboard::getForCurrentUser();
    }

    public function getDashboard()
    {
        if (!($dashboards = $this->getDashboards()) || !$dashboards->exists()) {
            return null;
        }

        $controller = Controller::curr();
        $request = $controller->getRequest();
        $dashboardId = $request->getVar('dashboard');
        if ($dashboardId && $dashboard = $dashboards->byID($dashboardId)) {
            return $dashboard;
        }

        return $dashboards->first();
    }

    public function DashboardLink($id)
    {
        $action = $this->sanitiseClassName(Panel::class) . "?dashboard=$id";
        return $this->Link($action);
    }

    protected function getManagedModelTabs()
    {   
        $tabs = new ArrayList();
        $currentUser = Security::getCurrentUser();
        
        $dashboards = $this->getDashboards();
        if ($dashboards->count() > 1 || ($currentUser && $currentUser->isDefaultAdmin())) {
            $currentDashboard = $this->getDashboard();
            foreach ($dashboards as $dashboard) {
                $tabs->push(new ArrayData(array(
                    'Title' => $dashboard->Title,
                    'Tab' => Panel::class,
                    'ClassName' => Panel::class,
                    'Link' => $this->DashboardLink($dashboard->ID),
                    'LinkOrCurrent' => $currentDashboard->ID === $dashboard->ID ? 'current' : 'link'
                )));
            }    
        }

        if (!$currentUser || !Permission::checkMember($currentUser, 'ADMIN')) {
            return $tabs;
        }
        
        $models = $this->getManagedModels();
        foreach ($models as $tab => $options) {
            if ($tab !== Panel::class) {
                $tabs->push(new ArrayData(array(
                    'Title' => $options['title'],
                    'Tab' => $tab,
                    'ClassName' => isset($options['dataClass']) ? $options['dataClass'] : $tab,
                    'Link' => $this->Link($this->sanitiseClassName($tab)),
                    'LinkOrCurrent' => ($tab == $this->modelTab) ? 'current' : 'link'
                )));
            }
        }

        return $tabs;
    }
}
