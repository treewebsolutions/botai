<?php
/* @var $this yii\web\View */

use common\widgets\ActiveForm;
use tws\helpers\Url;
use yii\helpers\Html;

?>
<?php if ($contractor->id == 1): ?>
<div class="section section-md">
    <div class="container-fluid">
        <?php $form = ActiveForm::begin([
            'id' => mb_strtolower($location->formName()),
            'options' => [
                'enctype' => 'multipart/form-data',
                'novalidate' => true,
            ],
            'validateOnType' => true,
        ]); ?>
        <div class="form-body">
            <div class="form-fields">
                <?php if ($location->hasErrors() && empty(array_intersect_key($location->getErrors(), array_flip($location->safeAttributes())))): ?>
                    <?= $location->errorSummary($location, [
                        'header' => false,
                        'class' => 'alert alert-danger alert-icon',
                    ]) ?>
                <?php endif; ?>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="text-info-icon fa-info-circle text-muted margin-bottom-10"><?= Yii::t('backend', 'Drag the map marker to the desired position.') ?></div>
                        <div id="map-container" style="width: 100%; height: 400px;"></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="alert alert-warning gap-t-md gap-b-md" role="alert"><?= Yii::t('common', 'To test the attendance from the application, you must set the coordinates so that they are within a maximum radius of 1 km from the device.') ?></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <?= $form->field($location, 'full_address')->textInput() ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($location, 'latitude')->textInput() ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($location, 'longitude')->textInput() ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <?= Html::checkbox('', true, [
                                    'id' => 'update-address-checkbox',
                                    'value' => 1,
                                    'label' => Yii::t('backend', 'Update coordinates while dragging the map marker'),
                                    'labelOptions' => [
                                        'class' => 'mt-checkbox mt-checkbox-outline ',
                                    ],
                                ]) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-actions floating">
                                    <?= Html::submitButton(Yii::t('common', 'Update'), [
                                        'class' => 'btn btn-default btn-slide-right btn-block',
                                    ]) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <script type="text/javascript">
            (function (window, document, undefined) {
                var defaultLat = "<?= $location->latitude ?: 45.7519379 ?>",
                    defaultLng = "<?= $location->longitude ?: 21.2259002 ?>",
                    latitudeControl = document.getElementById("<?= Html::getInputId($location, 'latitude') ?>"),
                    longitudeControl = document.getElementById("<?= Html::getInputId($location, 'longitude') ?>"),
                    addressControl = document.getElementById("<?= Html::getInputId($location, 'full_address') ?>"),
                    updateAddressCheckbox = document.getElementById("update-address-checkbox");

                var geocode = function (address) {
                    var geocoder = new google.maps.Geocoder();

                    geocoder.geocode({address: address}, function (results, status) {
                        if (status === google.maps.GeocoderStatus.OK) {
                            var addressComponents = [],
                                geometry = [],
                                latitude = '',
                                longitude = '',
                                locality = '',
                                county = '',
                                country = '',
                                zipCode = '',
                                streetName = '',
                                streetNumber = '';

                            for (var i = 0, len = results.length; i < len; i++) {
                                if (results[i].address_components) {
                                    addressComponents = results[i].address_components;
                                    break;
                                }
                            }
                            for (var i = 0, len = results.length; i < len; i++) {
                                if (results[i].geometry) {
                                    geometry = results[i].geometry;
                                    break;
                                }
                            }
                            for (i = 0, len = addressComponents.length; i < len; i++) {
                                if (addressComponents[i].types[0] === 'locality') {
                                    locality = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'administrative_area_level_1') {
                                    county = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'country') {
                                    country = addressComponents[i].short_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'postal_code') {
                                    zipCode = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'route') {
                                    streetName = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'street_number') {
                                    streetNumber = addressComponents[i].long_name.trim();
                                }
                            }

                            latitude = geometry.location.lat();
                            longitude = geometry.location.lng();

                            latitudeControl.value =  latitude;
                            longitudeControl.value =  longitude;

                            var map = new google.maps.Map(document.getElementById('map-container'), {
                                    zoom: 14,
                                    center: new google.maps.LatLng(latitude, longitude),
                                    mapTypeId: google.maps.MapTypeId.ROADMAP
                                }),
                                mapMarker = new google.maps.Marker({
                                    position: new google.maps.LatLng(latitude, longitude),
                                    draggable: true
                                });

                            google.maps.event.addListener(mapMarker, 'dragend', function (e) {
                                if (updateAddressCheckbox.checked) {
                                    reverseGeocode(e.latLng);
                                }
                                latitudeControl.value = e.latLng.lat().toFixed(8);
                                longitudeControl.value = e.latLng.lng().toFixed(8);
                            });

                            mapMarker.setMap(map);
                            map.setCenter(mapMarker.position);
                        }
                    });
                };

                var reverseGeocode = function (latLng) {
                    var geocoder = new google.maps.Geocoder();

                    geocoder.geocode({location: latLng}, function (results, status) {
                        if (status === google.maps.GeocoderStatus.OK) {
                            var addressComponents = [],
                                locality = '',
                                county = '',
                                country = '',
                                zipCode = '',
                                streetName = '',
                                streetNumber = '';

                            for (var i = 0, len = results.length; i < len; i++) {
                                if (results[i].address_components) {
                                    addressComponents = results[i].address_components;
                                    break;
                                }
                            }
                            for (i = 0, len = addressComponents.length; i < len; i++) {
                                if (addressComponents[i].types[0] === 'locality') {
                                    locality = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'administrative_area_level_1') {
                                    county = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'country') {
                                    country = addressComponents[i].short_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'postal_code') {
                                    zipCode = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'route') {
                                    streetName = addressComponents[i].long_name.trim();
                                }
                                if (addressComponents[i].types[0] === 'street_number') {
                                    streetNumber = addressComponents[i].long_name.trim();
                                }
                            }

                            addressControl.value = streetName;
                            if (streetName && streetNumber) {
                                addressControl.value += (', ' + streetNumber);
                            }
                            if (streetName && streetNumber && locality) {
                                addressControl.value += (', ' + locality);
                            }
                        }
                    });
                };

                window.initMap = function () {
                    var map = new google.maps.Map(document.getElementById('map-container'), {
                            zoom: 14,
                            center: new google.maps.LatLng(defaultLat, defaultLng),
                            mapTypeId: google.maps.MapTypeId.ROADMAP
                        }),
                        mapMarker = new google.maps.Marker({
                            position: new google.maps.LatLng(defaultLat, defaultLng),
                            draggable: true
                        });

                    google.maps.event.addListener(mapMarker, 'dragend', function (e) {
                        if (updateAddressCheckbox.checked) {
                            reverseGeocode(e.latLng);
                        }
                        latitudeControl.value = e.latLng.lat().toFixed(8);
                        longitudeControl.value = e.latLng.lng().toFixed(8);
                    });

                    addressControl.addEventListener('change', function (e) {
                        geocode(e.target.valueOf().value);
                    });

                    addressControl.addEventListener('keyup', function (e) {
                        var timeout = 0;
                        clearTimeout(timeout);
                        timeout = setTimeout(function() {
                            geocode(e.target.valueOf().value);
                        }, 500);
                    });

                    mapMarker.setMap(map);
                    map.setCenter(mapMarker.position);
                };
            })(window, document);
        </script>
        <script async defer src="//maps.googleapis.com/maps/api/js?key=<?= Yii::$app->settings->get('googleMapKey') ?>&language=<?= Yii::$app->language ?>&callback=initMap"></script>
    </div>
</div>
<?php endif; ?>

<div class="section section-md">
	<div class="container-fluid text-center">
        <?php if (!empty($contractor) && is_file(Yii::getAlias("@uploads/qr-code/{$contractor->id}/{$contractor->code}.png"))): ?>
            <img class="img-responsive inline-block" src="<?= Url::to("@uploads/qr-code/{$contractor->id}/{$contractor->code}.png", true) ?>" alt="<?= $contractor->name ?>">
	    <?php endif; ?>
        <br><br><br>
        <p>
            <?php if (Yii::$app->settings->get('appleStoreUrl')): ?>
                <a href="<?= Yii::$app->settings->get('appleStoreUrl') ?>" target="_blank" class="btn btn-store">
                    <span class="fa fa-apple fa-3x pull-left"></span>
                    <span class="btn-label">Download on the</span>
                    <span class="btn-caption">App Store</span>
                </a>
            <?php endif; ?>
	        <?php if (Yii::$app->settings->get('googlePlayUrl')): ?>
                <a href="<?= Yii::$app->settings->get('googlePlayUrl') ?>" target="_blank" class="btn btn-store">
                    <span class="fa fa-android fa-3x pull-left"></span>
                    <span class="btn-label">Download on the</span>
                    <span class="btn-caption">Google Play</span>
                </a>
	        <?php endif; ?>
        </p>
    </div>
</div>
