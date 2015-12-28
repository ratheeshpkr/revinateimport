jQuery(document).ready(function(){
  jQuery("#submit").click(function(e){
    if(confirm("Are you sure?")){
      //Ajax ->
      return true;
    }
    else{
      e.preventDefault();
      return false;
    }
  });
});
