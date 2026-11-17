<?php

test('the home page redirects to the questions list', function () {
    $response = $this->get('/');

    $response->assertRedirect('/questions');
});
