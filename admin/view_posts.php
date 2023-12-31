<?php

// Inkluderer tilkoblingsfilen for å koble til databasen
include '../components/connect.php';

// Starter sesjonen for å kunne lagre admininformasjon
session_start();

// Henter admin-ID fra sesjonen
$admin_id = $_SESSION['admin_id'];

// Sjekker om admin-ID er satt i sesjonen, ellers omdirigerer til innloggingssiden for administrator
if (!isset($admin_id)) {
   header('location:admin_login.php');
}

// Sjekker om det er sendt en forespørsel om å slette et innlegg
if (isset($_POST['delete'])) {

   // Henter innleggets ID fra skjemaet
   $p_id = $_POST['post_id'];
   $p_id = filter_var($p_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

   // Henter informasjon om bildet knyttet til innlegget for å slette det fra serveren
   $delete_image = $conn->prepare("SELECT * FROM `posts` WHERE id = ?");
   $delete_image->execute([$p_id]);
   $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);
   if ($fetch_delete_image['image'] != '') {
      unlink('../uploaded_img/' . $fetch_delete_image['image']);
   }

   // Sletter innlegget og tilhørende kommentarer fra databasen
   $delete_post = $conn->prepare("DELETE FROM `posts` WHERE id = ?");
   $delete_post->execute([$p_id]);
   $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE post_id = ?");
   $delete_comments->execute([$p_id]);

   // Gir en beskjed om at innlegget er slettet
   $message[] = 'Innlegget ble slettet vellykket!';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>innlegg</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
</head>

<body>
   <?php include '../components/admin_header.php' ?>
   <section class="show-posts">
      <h1 class="heading">Dine innlegg</h1>
      <div class="box-container">
         <?php
         // Henter informasjon om alle innlegg knyttet til den påloggede admin-brukeren
         $select_posts = $conn->prepare("SELECT * FROM `posts` WHERE admin_id = ?");
         $select_posts->execute([$admin_id]);
         if ($select_posts->rowCount() > 0) {
            while ($fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC)) {
               $post_id = $fetch_posts['id'];

               // Tell antall kommentarer og likerklikk for hvert innlegg
               $count_post_comments = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ?");
               $count_post_comments->execute([$post_id]);
               $total_post_comments = $count_post_comments->rowCount();

               $count_post_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
               $count_post_likes->execute([$post_id]);
               $total_post_likes = $count_post_likes->rowCount();

         ?>
               <form method="post" class="box">
                  <input type="hidden" name="post_id" value="<?= $post_id; ?>">
                  <?php if ($fetch_posts['image'] != '') { ?>
                     <img src="../uploaded_img/<?= $fetch_posts['image']; ?>" class="image" alt="">
                  <?php } ?>
                  <div class="status" style="background-color:<?php if ($fetch_posts['status'] == 'active') {
                                                                  echo 'limegreen';
                                                               } else {
                                                                  echo 'coral';
                                                               }; ?>;"><?= $fetch_posts['status']; ?></div>
                  <div class="title"><?= $fetch_posts['title']; ?></div>
                  <div class="posts-content"><?= $fetch_posts['content']; ?></div>
                  <div class="icons">
                     <div class="likes"><i class="fas fa-heart"></i><span><?= $total_post_likes; ?></span></div>
                     <div class="comments"><i class="fas fa-comment"></i><span><?= $total_post_comments; ?></span></div>
                  </div>
                  <div class="flex-btn">
                     <a href="edit_post.php?id=<?= $post_id; ?>" class="option-btn">Rediger</a>
                     <button type="submit" name="delete" class="delete-btn" onclick="return confirm('Slett innlegget?');">Slett</button>
                  </div>
                  <a href="read_post.php?post_id=<?= $post_id; ?>" class="btn">Vis innlegg</a>
               </form>
         <?php
            }
         } else {
            echo '<p class="empty">Ingen innlegg ble opprettet! <a href="add_posts.php" class="btn" style="margin-top:1.5rem;">Legg til innlegg</a></p>';
         }
         ?>
      </div>
   </section>
   <script src="../js/admin_script.js"></script>
</body>

</html>