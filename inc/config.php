<?php
define("HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "rangs_hr_rml");

$conn_hr = mysqli_connect(HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn_hr) {
	die(mysqli_error());
}


function getUserAccessRoleByID($id)
{
	global $conn_hr;
	$userRoleArray = [];
	$sql = "SELECT r.* FROM tbl_users_roles ur
        JOIN tbl_roles r ON ur.role_id = r.id
        WHERE ur.user_id = $id";
	$rs = mysqli_query($conn_hr, $sql);
	if (mysqli_num_rows($rs) > 0) {
		while ($row = mysqli_fetch_assoc($rs)) {
			array_push($userRoleArray, $row['name']);
		}
	} else {
		array_push($userRoleArray, "Public");
	}
	return $userRoleArray;
}

function checkPermission($permissionSlug)
{
	global $conn_hr;
	$isPermission = false;
	$permissionName = getUserWisePermissionName();

	if (count($permissionName) > 0) {
		$allperSlug = array_column($permissionName, 'slug');
		if (in_array($permissionSlug, $allperSlug)) {
			$isPermission = true;
		}
		return $isPermission;
	} else {
		$permissionArray = [];
		$permissionSlugData = [];
		$rolesql        = "SELECT * FROM tbl_roles_permissions  Where role_id =7"; //  select query execution
		$result     = mysqli_query($conn_hr, $rolesql);
		if ($result) {
			while ($row = mysqli_fetch_array($result)) {
				$permissionArray[] = array(
					'permission_id' => $row['permission_id']
				);
			}
		}
		$permissionArray = array_column($permissionArray, 'permission_id');
		foreach ($permissionArray as $key => $value) {
			$sql        = "SELECT * FROM tbl_permissions  Where id=" . $value; //  select query execution
			$perResult     = mysqli_query($conn_hr, $sql);
			if ($perResult) {
				while ($row = mysqli_fetch_array($perResult)) {
					$permissionSlugData[] = array(
						'slug' => $row['slug']
					);
				}
			}
		}
		$allperSlug = array_column($permissionSlugData, 'slug');
		if (in_array($permissionSlug, $allperSlug)) {
			$isPermission = true;
		}
		return $isPermission;
	}
}

function getUserWisePermissionName()
{
	global $conn_hr;
	$user_id = $_SESSION['HR_APPS']['id_hr'];
	$permissionArray = [];
	$permissionSlug = [];
	$sql        = "SELECT * FROM tbl_users_permissions  Where user_id=" . $user_id; //  select query execution
	$result     = mysqli_query($conn_hr, $sql);
	// Loop through the fetched rows
	if ($result) {
		while ($row = mysqli_fetch_array($result)) {
			$permissionArray[] = array(
				'permission_id' => $row['permission_id']
			);
		}
	}

	$permissionArray = array_column($permissionArray, 'permission_id');
	foreach ($permissionArray as $key => $value) {
		$sql        = "SELECT * FROM tbl_permissions  Where id=" . $value; //  select query execution
		$perResult     = mysqli_query($conn_hr, $sql);
		if ($perResult) {
			while ($row = mysqli_fetch_array($perResult)) {
				$permissionSlug[] = array(
					'slug' => $row['slug']
				);
			}
		}
	}
	return $permissionSlug;
}

function getUserWiseRoleName($roleSlug)
{
	global $conn_hr;
	$rolePermission = false;
	$user_id = $_SESSION['HR_APPS']['id_hr'];
	$sql        = "SELECT id FROM tbl_roles  WHERE slug = '$roleSlug'"; //  select query execution
	$perResult  = mysqli_query($conn_hr, $sql);
	$dataRow 	= mysqli_fetch_assoc($perResult);
	if (count($dataRow) > 0) {
		$sql2        = "SELECT * FROM tbl_users_roles  WHERE  user_id = $user_id  AND role_id =" . $dataRow['id']; 
		$result  	= mysqli_query($conn_hr, $sql2);
		$dataRow2 	= mysqli_fetch_assoc($result);
		if (($dataRow2) > 0) {
			$rolePermission = true;
		} else {
			$rolePermission = false;
		}
	} else {
		$rolePermission = false;
	}
	return $rolePermission;
}