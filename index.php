<?php session_start() ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Wikipedia Parse data</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
<form action="../parse_data/process.php" method="POST">
  <div class="form-group">
    <label for="name">Enter Wikipedia URL:</label>
    <input type="text" class="form-control" id="name" name="name" value="<?php if (isset ($_SESSION['name']) && !empty($_SESSION['name'])) { echo $_SESSION['name'];} else { echo ""; }?>"> 
    <?php if (isset ($_SESSION['validate']['name']) && !empty($_SESSION['validate']['name'])) { ?>
    <div class="alert alert-danger" role="alert">* <?php echo $_SESSION['validate']['name']; ?></div>
    <?php } ?>
  </div>
<button type="submit" class="btn btn-default">Submit</button>
</form>       
</div>
</body>
</html>
