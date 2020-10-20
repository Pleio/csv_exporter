<?php
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=\"export.csv\"");
elgg_make_sticky_form("csv_exporter");

$type_subtype = get_input("type_subtype");
$exportable_values = get_input("exportable_values");

list($type, $subtype) = explode(":", $type_subtype);

$fh = fopen("php://output", "w"); // stream to output buffer

$available_values = csv_exporter_get_exportable_values($type, $subtype, true);
$headers = array();
foreach ($exportable_values as $export_value) {
	$headers[] = array_search($export_value, $available_values);
}

// headers
fwrite($fh, "\"" . implode("\";\"", $headers) . "\"" . PHP_EOL);

function row_to_guid($row) {
    return $row->guid;
}

$options = array(
	"type" => $type,
	"subtype" => $subtype,
    "callback" => "row_to_guid",
	"limit" => false
);

if ($type == "user") {
	$options["relationship"] = "member_of_site";
	$options["relationship_guid"] = elgg_get_site_entity()->getGUID();
	$options["inverse_relationship"] = true;
}

$entities = elgg_get_entities_from_relationship($options);
foreach ($entities as $guid) {

    $entity = get_entity($guid);

	if (!$entity) {
		continue;
	}

	$values = array();
	// params for hook
	$params = array(
		"type" => $type,
		"subtype" => $subtype,
		"entity" => $entity,
	);

	foreach ($exportable_values as $export_value) {
		$params["exportable_value"] = $export_value;
		$value = elgg_trigger_plugin_hook("export_value", "csv_exporter", $params);
		if ($value === null) {
			$value = $entity->$export_value;
		}
		$values[] = $value;
	}

	// row
	fwrite($fh, "\"" . implode("\";\"", $values) . "\"" . PHP_EOL);
}

fclose($fp);
exit();