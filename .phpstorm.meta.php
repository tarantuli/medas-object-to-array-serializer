<?php

namespace PHPSTORM_META {

    use Medas\ObjectToArraySerializer\ArrayToObjectCaster;

    override(ArrayToObjectCaster::cast(1), map([
        '' => '@',
    ]));
}
