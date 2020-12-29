<?php 
    $host="localhost";
    $dbase="u149072_tzrktv";
    $user = "u149072_root";
    $password="homka1213";
    $link = mysqli_connect($host, $user, $password, $dbase);
    //Подключение файлов
    require 'db.php';
    //получение всех записей таблицы
    $query2 = $link->query("SELECT COUNT(*) FROM polz");
    $total = $query2->fetch_row();
    $count = $total[0];
    $upd = 0;
    $del= 0;
    $process = 0;
    //аргументы пагинации
    if(isset($_GET['o']) && is_numeric($_GET['o'])){
        $o = $_GET['o'];
    }
    else{
        $o=0;
    }
    if($o >= 5){
        $prev= $o-5;
    }
    else{
        $prev=0;
    }
    $next = $o+5;
    //Сортировка
    if(isset($_GET['order'])){
        $order=$_GET['order'];
    }
    else{
        $order='';
    }
    switch($order){
        case 'NameA':
            $order_sql = 'ORDER BY Imya ASC';
            break;
        case 'NameD':
            $order_sql = 'ORDER BY Imya DESC';
            break;
        case 'EmailA':
            $order_sql = 'ORDER BY email ASC';
            break;
        case 'EmailD':
            $order_sql = 'ORDER BY email DESC';
            break;
    }
    //Настройка формы импорта
    if(isset($_POST['buttonImport'])){
        if(isset($_FILES['xmlFile']['name']) && $_FILES['xmlFile']['name'] != '') { //проверка выбран ли файл
            copy($_FILES['xmlFile']['tmp_name'],
            'files/'.$_FILES['xmlFile']['name']);
            $data = simplexml_load_file('files/'.$_FILES['xmlFile']['name']);
            //Запросы
            foreach($data as $user){
                $query = $connect->prepare("INSERT into `polz` (Loginn, Imya, email) values(:Loginn, :Imya, :email)");
                $email = $user->login.'@example.com';
                $name = $user->login;
                $query->bindParam(':Loginn', $user->login);
                $query->bindParam(':Imya', $name);
                $query->bindParam(':email', $email);
                $query->execute();
            }
        
            $info = $query->errorInfo();
            print_r($info);
        }
        // else{
        //     echo "Файл не выбран";
        // }
    }
    //Настройка формы upd
    if(isset($_POST['buttonUpdate'])){
        if(isset($_FILES['xmlFile']['name']) && $_FILES['xmlFile']['name'] != '') { //проверка выбран ли файл
            copy($_FILES['xmlFile']['tmp_name'],
            'files/'.$_FILES['xmlFile']['name']);
            $data = simplexml_load_file('files/'.$_FILES['xmlFile']['name']);
            //Upd
            foreach($data as $user){
                $login = $user->login;
                $name = $user->login.'Ke';
                $email = $user->login.'@gcom';
                $query = $connect->prepare("SELECT Imya,email FROM `polz` WHERE Loginn = ?");
                $query->execute(array($login));
                $res = $query->fetch();
                $process += 1;
                if(!empty($res)){
                    $query=$connect->prepare("UPDATE polz SET Imya=?, email=? WHERE Loginn = ?");
                    $query->execute(array($name,$email,$login));
                    $upd +=1;
                }
            }
            //Удаление пользователя, которого нет в файле
            $query = $connect->prepare("SELECT Loginn FROM polz");
            $query->execute();
            $usersList = $query->fetchall();
            $users = [];
            $usersFile =[];
            foreach($usersList as $user) $users[] = $user['Loginn']; //users из БД
            foreach($data as $user) {
                $login = $user->login;
                $usersFile[] = $login; 
                if(!in_array($login, $users)) {
                    mysqli_query($link, "DELETE FROM polz WHERE Loginn = '$login'");
                }
            }
            $i= 0;
            foreach($users as $user){
                $login = $users[$i]." ";
                $login = trim($login);
                if(!in_array($login, $usersFile)){
                    mysqli_query($link, "DELETE FROM polz WHERE Loginn = '$login'");
                    $del += 1; 
                }
                $i++;
            }
            header('Location:index.php?o=0');
        }
        // else{
        // echo "Вы не выбрали файл!";
        // }
    }
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style/style.css">
</head>
    <body>
        <!-- Форма загрузки файла для импорта -->
        <form method="post" id="import_form" enctype="multipart/form-data">
            <label>Выберите файл</label>
            <input class="uploadFile" type="file" name="xmlFile"/>
            <br/>
            <div class ="items">
                <input  type="submit" class ="Impr" name="buttonImport" value="Import" />
                <input  type="submit" class ="updinp" name="buttonUpdate" value="Update" />
            </div>  
        </form>
        <!-- вывод таблицы -->
        <div class ="tab">
            <table>        
                <tr>
                    <th> <a class ="sort" href="index.php?order=NameA&o=<?php echo $o?>"> ↑  </a> Name  <a class ="sort" href="index.php?order=NameD&o=<?php echo $o?>"> ↓  </a> </th>
                    <th> <a class ="sort" href="index.php?order=EmailA&o=<?php echo $o?>"> ↑ </a> Email <a class ="sort" href="index.php?order=EmailD&o=<?php echo $o?>"> ↓ </a> </th> 
                </tr>
                <?php
                    $query3= "SELECT Loginn, Imya, email FROM polz ". $order_sql ." LIMIT $o, 5";
                    $sql = mysqli_query($link, $query3);
                    while ($result = mysqli_fetch_array($sql)) {
                    echo '<tr>' .
                        "<td>{$result['Imya']}</td>" .
                        "<td>{$result['email']}</td>" .
                        '</tr>';
                    }	 
                ?>
            </table>
        </div>           
        <!-- Пагинация-->
        <?php  while($result = mysqli_fetch_assoc($sql)); ?>
        <div class = "undertableitem">
            <?php if($o > 0) {?>  
            <span>
                <a class="back" href ="index.php?o=<?php echo $prev ?>"><<</a>
            </span>
            <span> Всего:
                <?php echo $count ?>
            </span>
            <?php }?>
            <span class ="processedString"> 
                <?php echo 'Обработано строк: '.$process; ?>
            </span>
            <span class ="deleteString"> 
                <?php echo 'Удалено строк: '.$del; ?> 
            </span>
            <span class="updateString"> 
                <?php echo 'Обновлено строк: '.$upd; ?>
            </span>
            <span>
                <?php if($o<=$count-5) {?> 
                <a class="next" href="index.php?o=<?php echo $next ?>"> >></a> 
            </span>
        </div>
        <?php }?>
    </body>
</html>
