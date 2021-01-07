document.addEventListener('DOMContentLoaded', () => {
    console.log("Event js works!")

    const tabBaseData = document.querySelector('#tabBaseData');
    const tabSecurity = document.querySelector('#tabSecurity');
    const tabUnderline = document.querySelector('.blueTabUnderline');
    const baseData = document.querySelector('.containerBaseData');
    const baseSecurity = document.querySelector('.containerSecurity');
    const hamMenuBtn = document.querySelector('.hamBtn');
    const navListContainer = document.querySelector('.navigationListContainer');


    //onClick tab in new-application site

    // tabBaseData.addEventListener('click', () => {
    //     baseData.classList.add("doActive");
    //     tabBaseData.style.color = "#079EE0";
    //     tabSecurity.style.color = "#222222";
    //     tabUnderline.style.transform = "translateX(0px)";
    //     baseSecurity.classList.remove("doActive");

    //     let move = 145;
        
    //     let interval = setInterval(slide, 10);

    //     function slide(){
    //         if(move === 0){
    //           clearInterval(interval);      
    //         } else {
    //             move -= 5;
    //             tabUnderline.style.transform = "translateX" + "(" +  move + "px" + ")";

    //         }
    //     }

        
    // })

    // tabSecurity.addEventListener('click', () => {
    //     baseSecurity.classList.add("doActive");
    //     tabSecurity.style.color = "#079EE0";
    //     tabBaseData.style.color = "#222222";
    //     // tabUnderline.style.transform = "translateX(145px)";
    //     baseData.classList.remove("doActive");

    //     let move = 0;
    //     let interval = setInterval(slide, 10);

    //     function slide(){
    //         if(move === 145){
    //           clearInterval(interval);      
    //         } else {
    //             move += 5;
    //             tabUnderline.style.transform = "translateX" + "(" +  move + "px" + ")";

    //         }
    //     }

    //     console.log("move:", move);

        
    // })


    hamMenuBtn.addEventListener('click', () => {
        if(navListContainer.classList.contains("navigationListContainerHam")){
            navListContainer.classList.remove("navigationListContainerHam");
        } else {
            navListContainer.classList.add("navigationListContainerHam");
        // navListContainer.style.display = "block";
        }
        
    });
});
