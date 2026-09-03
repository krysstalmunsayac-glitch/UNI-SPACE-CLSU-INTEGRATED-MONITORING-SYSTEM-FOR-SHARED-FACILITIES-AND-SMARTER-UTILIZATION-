<?php

it('shows a friendly page not found message', function () {
    $this->get('/does-not-exist')
        ->assertNotFound()
        ->assertSee('Page not found');
});
