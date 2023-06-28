<?php

namespace App\Spec;

use Devsrealm\TonicsRouterSystem\RequestInput;

describe("RequestInput", function () {

    /*** @var RequestInput $requestInput */
    $requestInput = $this->router->wireRouter()->getResponse()->getRequestInput();
    $data = [
        'one' => [
            'two' => [
                'three' => 'four'
            ]
        ],
        1 =>
            [ 2 =>
                [3 => [4 => 5] ]
            ]
    ];

    #
    # There is no point in testing fromFile, fromGet, and fromServer, they are 100% identical to fromPost.
    #
    # Also, I am only testing the crucial methods and or public methods
    #
    describe("->fromPost()", function () use ($data, $requestInput) {

        describe("->has()", function () use ($data, $requestInput) {
            
            context("When key is 'one.two' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->has("one.two"))->toBeTruthy();
                });
            });

            context("When key is 'one.two.three' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->has("one.two.three"))->toBeTruthy();
                });
            });

            context("When key is '1' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->has("1"))->toBeTruthy();
                });
            });

            context("When key is '1.4.3.4.5' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->has("1.4.3.4.5"))->toBeFalsy();
                });
            });
        });

        describe("->hasValue()", function () use ($data, $requestInput) {

            context("When key is 'one.two' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->hasValue("one.two"))->toBeTruthy();
                });
            });

            context("When key is 'one.two.three' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->hasValue("one.two.three"))->toBeTruthy();
                });
            });

            context("When key is '1' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->hasValue("1"))->toBeTruthy();
                });
            });

            context("When key is 'nan.nan.nan' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->hasValue("nan.nan.nan"))->toBeFalsy();
                });
            });

            context("When key is '1.4.3.4.5' ", function () use ($data, $requestInput) {
                it("should return true", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->hasValue("1.4.3.4.5"))->toBeFalsy();
                });
            });
        });

        describe("->retrieve()", function () use ($data, $requestInput) {
            context("When key is 'one.two' ", function () use ($data, $requestInput) {
                it("should return key value", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->retrieve("one.two"))->toEqual($data['one']['two']);
                });
            });

            context("When key is 'one.two.three' ", function () use ($data, $requestInput) {
                it("should return key value", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->retrieve("one.two.three"))->toEqual($data['one']['two']['three']);
                });
            });

            context("When key is '1' ", function () use ($data, $requestInput) {
                it("should return key value", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->retrieve("1"))->toEqual($data[1]);
                });
            });

            context("When key is 'nan' ", function () use ($data, $requestInput) {
                it("should return an empty string", function () use ($data, $requestInput) {
                    expect($requestInput->fromPost($data)->retrieve("nam"))->toBeEmpty();
                });
            });
        });

    });


});