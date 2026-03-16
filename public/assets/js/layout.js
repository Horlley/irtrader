document.addEventListener("DOMContentLoaded", function () {

    let btn = document.getElementById("menuToggle");
    let sidebar = document.querySelector(".sidebar");
    let main = document.querySelector(".main");

    btn.addEventListener("click", function () {

        sidebar.classList.toggle("collapsed");

        if (sidebar.classList.contains("collapsed")) {
            sidebar.style.width = "70px";
            main.style.marginLeft = "70px";
        }
        else {
            sidebar.style.width = "240px";
            main.style.marginLeft = "240px";
        }

    });

});