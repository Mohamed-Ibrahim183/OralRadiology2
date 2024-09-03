<?php
interface LoginStrategy
{
    public function login($identifier, $password);
}
