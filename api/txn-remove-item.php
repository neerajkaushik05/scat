<?
include '../scat.php';

$details= array();

$txn= (int)$_REQUEST['txn'];
$id= (int)$_REQUEST['id'];

if (!$txn || !$id) die_jsonp('No transaction or item specified');

$q= "DELETE FROM txn_line WHERE txn = $txn AND id = $id";

$r= $db->query($q);
if (!$r) {
  die(json_encode(array('error' => 'Query failed. ' . $db->error,
                        'query' => $q)));
}
if (!$db->affected_rows) {
  die(json_encode(array('error' => "Unable to delete line.")));
}

echo json_encode(array('removed' => $id));