<?php

it('returns a successful response with favicon configured', function () {
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('favicon-32x32.png')
        ->assertSee('favicon-32x32.ico');
});
