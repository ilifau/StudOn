<html>
<body>
Bitte hier einfach irgendetwas eingeben (nicht Ihr echtes Passwort):
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <table border=1>
        <tr><td>Name: </td><td><input type="text" name="username"/></td></tr>
        <tr><td>PW: </td><td><input type="text" name="password" /></td></tr>
        <tr><td colspan=2><input type="submit" name="cmd[doStandardAuthentication]" value="Absenden" /></td>
    </table>
</form>
<?php
echo "benutzername: ". $_POST['username']. "<br>\n";
echo "passwort: ". $_POST['password']. "<br>\n";
echo "absenden: ". $_POST['cmd']['doStandardAuthentication'] . "<br>\n";
?>
</body>
</html>
