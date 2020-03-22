<?php

/**
 * Fetches the current metrics instance so the user can manipulate it (add some context, for example.)
 * 
 * @return Bottleneck\Metrics
 */
function bottleneck()
{
    return app('Bottleneck\Metrics');
}
