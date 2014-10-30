<?php

namespace MariusMandal\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MariusMandalUserBundle extends Bundle
{

    public function getParent()
    {
        return 'HWIOAuthBundle';
    }
}
