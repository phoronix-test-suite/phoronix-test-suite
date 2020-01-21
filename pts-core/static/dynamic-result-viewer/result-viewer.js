function delete_result_file(id)
{
	if(confirm("Really delete the '" + id + "' result file permanently?"))
	{
		window.location.href = "/index.php?remove_result=" + id;
	}
}
