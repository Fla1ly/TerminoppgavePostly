<?php

// Inkluderer tilkoblingsfilen for å koble til databasen
include 'components/connect.php';

// Starter sesjonen for å kunne lagre brukerinformasjon
session_start();

// Sjekker om brukeren allerede er logget inn ved å se etter bruker-ID i sesjonen
if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
} else {
   // Hvis ikke, setter bruker-ID til tom streng
   $user_id = '';
}

// Inkluderer filen for å behandle likes på innlegg
include 'components/like_post.php';

// Henter post-ID fra URL-parameteren
$get_id = $_GET['post_id'];

// Legger til kommentar hvis skjemaet er sendt
if (isset($_POST['add_comment'])) {
   $admin_id = $_POST['admin_id'];
   $admin_id = filter_var($admin_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $user_name = $_POST['user_name'];
   $user_name = filter_var($user_name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $comment = $_POST['comment'];
   $comment = filter_var($comment, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

   // Verifiserer om kommentaren allerede eksisterer
   $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ? AND admin_id = ? AND user_id = ? AND user_name = ? AND comment = ?");
   $verify_comment->execute([$get_id, $admin_id, $user_id, $user_name, $comment]);

   if ($verify_comment->rowCount() > 0) {
      $message[] = 'Kommentar allerede lagt til!';
   } else {
      // Legger til ny kommentar i databasen
      $insert_comment = $conn->prepare("INSERT INTO `comments` (post_id, admin_id, user_id, user_name, comment) VALUES (?,?,?,?,?)");
      $insert_comment->execute([$get_id, $admin_id, $user_id, $user_name, $comment]);
      $message[] = 'Ny kommentar lagt til!';
   }
}

// Redigerer kommentar hvis skjemaet er sendt
if (isset($_POST['edit_comment'])) {
   $edit_comment_id = $_POST['edit_comment_id'];
   $edit_comment_id = filter_var($edit_comment_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $comment_edit_box = $_POST['comment_edit_box'];
   $comment_edit_box = filter_var($comment_edit_box, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

   // Verifiserer om den redigerte kommentaren allerede eksisterer
   $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE comment = ? AND id = ?");
   $verify_comment->execute([$comment_edit_box, $edit_comment_id]);

   if ($verify_comment->rowCount() > 0) {
      $message[] = 'Kommentar allerede lagt til!';
   } else {
      // Oppdaterer kommentaren i databasen
      $update_comment = $conn->prepare("UPDATE `comments` SET comment = ? WHERE id = ?");
      $update_comment->execute([$comment_edit_box, $edit_comment_id]);
      $message[] = 'Kommentaren din ble redigert!';
   }
}

// Sletter kommentar hvis skjemaet er sendt
if (isset($_POST['delete_comment'])) {
   $delete_comment_id = $_POST['comment_id'];
   $delete_comment_id = filter_var($delete_comment_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE id = ?");
   $delete_comment->execute([$delete_comment_id]);
   $message[] = 'Kommentar slettet!';
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
   <link rel="stylesheet" href="css/style.css">
</head>

<body>
   <?php include 'components/user_header.php'; ?>
   <?php
   // Viser redigeringsformen for kommentarer hvis skjemaet er sendt
   if (isset($_POST['open_edit_box'])) {
      $comment_id = $_POST['comment_id'];
      $comment_id = filter_var($comment_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   ?>
      <section class="comment-edit-form">
         <p>Rediger din kommentar</p>
         <?php
         // Henter den redigerte kommentaren basert på kommentar-ID
         $select_edit_comment = $conn->prepare("SELECT * FROM `comments` WHERE id = ?");
         $select_edit_comment->execute([$comment_id]);
         $fetch_edit_comment = $select_edit_comment->fetch(PDO::FETCH_ASSOC);
         ?>
         <form action="" method="POST">
            <input type="hidden" name="edit_comment_id" value="<?= $comment_id; ?>">
            <textarea name="comment_edit_box" required cols="30" rows="10" placeholder="Please enter your comment"><?= $fetch_edit_comment['comment']; ?></textarea>
            <button type="submit" class="inline-btn" name="edit_comment">Rediger kommentar</button>
            <div class="inline-option-btn" onclick="window.location.href = 'view_post.php?post_id=<?= $get_id; ?>';">Avbryt redigering</div>
         </form>
      </section>
   <?php
   }
   ?>
   <section class="posts-container" style="padding-bottom: 0;">
      <div class="box-container">
         <?php
         // Henter innlegget basert på post-ID og status
         $select_posts = $conn->prepare("SELECT * FROM `posts` WHERE status = ? AND id = ?");
         $select_posts->execute(['active', $get_id]);
         if ($select_posts->rowCount() > 0) {
            while ($fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC)) {

               $post_id = $fetch_posts['id'];

               // Henter antall kommentarer for innlegget
               $count_post_comments = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ?");
               $count_post_comments->execute([$post_id]);
               $total_post_comments = $count_post_comments->rowCount();

               // Henter antall likes for innlegget
               $count_post_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
               $count_post_likes->execute([$post_id]);
               $total_post_likes = $count_post_likes->rowCount();

               // Bekrefter om brukeren har likt innlegget
               $confirm_likes = $conn->prepare("SELECT * FROM `likes` WHERE user_id = ? AND post_id = ?");
               $confirm_likes->execute([$user_id, $post_id]);
         ?>
               <form class="box" method="post">
                  <input type="hidden" name="post_id" value="<?= $post_id; ?>">
                  <input type="hidden" name="admin_id" value="<?= $fetch_posts['admin_id']; ?>">
                  <div class="post-admin">
                     <i class="fas fa-user"></i>
                     <div>
                        <a href="author_posts.php?author=<?= $fetch_posts['name']; ?>"><?= $fetch_posts['name']; ?></a>
                        <div><?= $fetch_posts['date']; ?></div>
                     </div>
                  </div>
                  <?php
                  if ($fetch_posts['image'] != '') {
                  ?>
                     <img src="uploaded_img/<?= $fetch_posts['image']; ?>" class="post-image" alt="">
                  <?php
                  }
                  ?>
                  <div class="post-title"><?= $fetch_posts['title']; ?></div>
                  <div class="post-content"><?= $fetch_posts['content']; ?></div>
                  <div class="icons">
                     <div><i class="fas fa-comment"></i><span><?= $total_post_comments; ?></span></div>
                     <button type="submit" name="like_post"><i class="fas fa-heart" style="<?php if ($confirm_likes->rowCount() > 0) {
                                                                                                echo 'color:var(--red);';
                                                                                             } ?>  "></i><span><?= $total_post_likes; ?></span></button>
                  </div>
               </form>
         <?php
            }
         } else {
            echo '<p class="empty">Ingen innlegg ble funnet!</p>';
         }
         ?>
      </div>
   </section>
   <section class="comments-container">
      <p class="comment-title">Legg til kommentar</p>
      <?php
      // Viser kommentarskjemaet hvis brukeren er logget inn
      if ($user_id != '') {
         $select_admin_id = $conn->prepare("SELECT * FROM `posts` WHERE id = ?");
         $select_admin_id->execute([$get_id]);
         $fetch_admin_id = $select_admin_id->fetch(PDO::FETCH_ASSOC);
      ?>
         <form action="" method="post" class="add-comment">
            <input type="hidden" name="admin_id" value="<?= $fetch_admin_id['admin_id']; ?>">
            <input type="hidden" name="user_name" value="<?= $fetch_profile['name']; ?>">
            <p class="user"><i class="fas fa-user"></i><a href="update.php"><?= $fetch_profile['name']; ?></a></p>
            <textarea name="comment" maxlength="1000" class="comment-box" cols="30" rows="10" placeholder="Skriv din kommentar" required></textarea>
            <input type="submit" value="Legg til kommentar" class="inline-btn" name="add_comment">
         </form>
      <?php
      } else {
      ?>
         <div class="add-comment">
            <p>Vennligst logg inn for å legge til eller redigere din kommentar</p>
            <a href="login.php" class="inline-btn">Logg inn</a>
         </div>
      <?php
      }
      ?>
      <p class="comment-title">Kommentarer:</p>
      <div class="user-comments-container">
         <?php
         // Henter kommentarene for det aktuelle innlegget
         $select_comments = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ?");
         $select_comments->execute([$get_id]);
         if ($select_comments->rowCount() > 0) {
            while ($fetch_comments = $select_comments->fetch(PDO::FETCH_ASSOC)) {
         ?>
               <div class="show-comments" style="<?php if ($fetch_comments['user_id'] == $user_id) {
                                                      echo 'order:-1;';
                                                   } ?>">
                  <div class="comment-user">
                     <i class="fas fa-user"></i>
                     <div>
                        <span><?= $fetch_comments['user_name']; ?></span>
                        <div><?= $fetch_comments['date']; ?></div>
                     </div>
                  </div>
                  <div class="comment-box" style="<?php if ($fetch_comments['user_id'] == $user_id) {
                                                      echo 'color:var(--white); background:var(--black);';
                                                   } ?>"><?= $fetch_comments['comment']; ?></div>
                  <?php
                  // Viser redigerings- og slettemuligheter hvis kommentaren tilhører brukeren
                  if ($fetch_comments['user_id'] == $user_id) {
                  ?>
                     <form action="" method="POST">
                        <input type="hidden" name="comment_id" value="<?= $fetch_comments['id']; ?>">
                        <button type="submit" class="inline-option-btn" name="open_edit_box">Rediger kommentar</button>
                        <button type="submit" class="inline-delete-btn" name="delete_comment" onclick="return confirm('Slett denne kommentaren?');">Slett kommentar</button>
                     </form>
                  <?php
                  }
                  ?>
               </div>
         <?php
            }
         } else {
            echo '<p class="empty">Ingen kommentar ble lagt til ennå!</p>';
         }
         ?>
      </div>
   </section>
   <script src="js/script.js"></script>
</body>

</html>