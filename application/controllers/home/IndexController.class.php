<?php

class IndexController extends Controller
{
    public function indexAction() {
        require CUR_VIEW_PATH . 'index.html';
    }
}