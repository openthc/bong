<?php
/**
 * The Product Types for CCRS
 *
 * SPDX-License-Identifier: MIT
 */

$res = [];
$res[] = 'PropagationMaterial/Plant';
$res[] = 'PropagationMaterial/Seed';
$res[] = 'HarvestedMaterial/Wet Flower';
$res[] = 'HarvestedMaterial/Wet Other Material';
$res[] = 'HarvestedMaterial/Flower Unlotted';
$res[] = 'HarvestedMaterial/Flower Lot';
$res[] = 'HarvestedMaterial/Other Material Unlotted';
$res[] = 'HarvestedMaterial/Other Material Lot';
$res[] = 'HarvestedMaterial/Marijuana Mix';
$res[] = 'HarvestedMaterial/Waste';
$res[] = 'IntermediateProduct/Marijuana Mix';
$res[] = 'IntermediateProduct/Concentrate for Inhalation';
$res[] = 'IntermediateProduct/Non-Solvent based Concentrate';
$res[] = 'IntermediateProduct/Hydrocarbon Concentrate';
$res[] = 'IntermediateProduct/CO2 Concentrate';
$res[] = 'IntermediateProduct/Ethanol Concentrate';
$res[] = 'IntermediateProduct/Food Grade Solvent Concentrate';
$res[] = 'IntermediateProduct/Infused Cooking Medium';
$res[] = 'IntermediateProduct/CBD';
$res[] = 'IntermediateProduct/Waste';
$res[] = 'EndProduct/Capsule';
$res[] = 'EndProduct/Solid Edible';
$res[] = 'EndProduct/Tincture';
$res[] = 'EndProduct/Liquid Edible';
$res[] = 'EndProduct/Transdermal';
$res[] = 'EndProduct/Topical Ointment';
$res[] = 'EndProduct/Marijuana Mix Packaged';
$res[] = 'EndProduct/Marijuana Mix Infused';
$res[] = 'EndProduct/Suppository';
$res[] = 'EndProduct/Usable Marijuana';
$res[] = 'EndProduct/Sample Jar';
$res[] = 'EndProduct/Waste';

sort($res);

return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
