document.addEventListener("DOMContentLoaded", function () {
  loadGoogleMapsAPI();

  // Add click event listeners to job cards
  document.querySelectorAll(".job-card").forEach((card) => {
    card.addEventListener("click", () => updateJobOffers(card));
  });
});

function loadGoogleMapsAPI() {
  if (typeof google === "undefined") {
      const script = document.createElement("script");
      script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyDtbgRcgki0qgsq4Kt6c0JlhhUhEUH7PXQ&libraries=geometry&callback=initMap`;
      script.async = true;
      script.defer = true;
      script.onerror = function () {
          handleLocationError("Failed to load Google Maps API");
      };
      document.head.appendChild(script);
  } else {
      initMap();
  }
}

function initMap() {
  getUserLocation();
}

function getUserLocation() {
  if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(successCallback, errorCallback, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0,
      });
  } else {
      handleLocationError("Geolocation is not supported by this browser.");
  }
}

function successCallback(position) {
  const userLat = position.coords.latitude;
  const userLon = position.coords.longitude;

  const jobCards = document.querySelectorAll(".job-card");
  const jobCardsArray = Array.from(jobCards);

  let validDistanceFound = false;

  jobCardsArray.forEach((card) => {
      const distanceElement = card.querySelector(".company-distance");
      const companyLat = parseFloat(distanceElement.getAttribute("data-lat"));
      const companyLon = parseFloat(distanceElement.getAttribute("data-lon"));

      if (!companyLat || !companyLon || isNaN(companyLat) || isNaN(companyLon)) {
          card.distance = Infinity;
          distanceElement.textContent = "Distance unavailable";
          return;
      }

      try {
          const distance = google.maps.geometry.spherical.computeDistanceBetween(
              new google.maps.LatLng(userLat, userLon),
              new google.maps.LatLng(companyLat, companyLon)
          );

          const distanceKm = (distance / 1000).toFixed(2);
          card.distance = parseFloat(distanceKm);
          distanceElement.textContent = `${distanceKm} km away`;
          validDistanceFound = true;
      } catch (error) {
          console.error("Error calculating distance:", error);
          card.distance = Infinity;
          distanceElement.textContent = "Error calculating distance";
      }
  });

  if (validDistanceFound) {
      jobCardsArray.sort((a, b) => a.distance - b.distance);
      const jobListings = document.querySelector(".job-listings");
      const noResults = jobListings.querySelector(".no-results");
      jobListings.innerHTML = "";
      
      jobCardsArray.forEach((card) => {
          jobListings.appendChild(card);
          card.addEventListener("click", () => updateJobOffers(card));
      });

      if (noResults && !validDistanceFound) {
          jobListings.appendChild(noResults);
      }

      if (jobCardsArray.length > 0) {
          updateJobOffers(jobCardsArray[0]);
      }
  }
}

function errorCallback(error) {
  handleLocationError(`Error getting location: ${error.message}`);
}

function handleLocationError(message) {
  console.error(message);
  const distanceElements = document.getElementsByClassName("company-distance");
  Array.from(distanceElements).forEach((element) => {
      element.textContent = "Distance unavailable";
  });
}

function updateJobOffers(card) {
    const jobOffers = document.querySelector(".job-offers");
    document.querySelectorAll(".job-card").forEach((c) => c.classList.remove("active"));
    card.classList.add("active");
  
    const internshipId = card.getAttribute("data-id");
    const companyLogo = card.getAttribute("data-company-logo");
    const companyName = card.getAttribute("data-company-name");
    const companyAddress = card.getAttribute("data-company-address");
    const internshipTitle = card.getAttribute("data-internship-title");
    const internshipType = card.getAttribute("data-internship-type");
    const internshipDescription = card.getAttribute("data-internship-description");
    const numberOfOpenings = card.getAttribute("data-number-of-openings");
    const duration = card.getAttribute("data-duration");
    const hasApplied = card.getAttribute("data-has-applied") === "1";
    const department = card.getAttribute("data-department");
    const requirements = card.getAttribute("data-requirements");
    const qualifications = card.getAttribute("data-qualifications");
    const skillsRequired = card.getAttribute("data-skills-required");
    const applicationDeadline = card.getAttribute("data-application-deadline");
    const additionalInfo = card.getAttribute("data-additional-info");
    const postedTime = card.getAttribute("data-posted-time");
    const companyId = card.getAttribute("data-company-id");
    const internshipSummary = card.getAttribute("data-internship-summary");
    const companyOverview = card.getAttribute("data-company-overview");
    const latitude = card.getAttribute("data-latitude");
    const longitude = card.getAttribute("data-longitude");
    const companyWebsite = card.getAttribute("data-company-email");
    const loginMethod = card.getAttribute("data-login-method");
    const totalRating = parseFloat(card.getAttribute("data-total-rating")) || 0;
    const totalReviews = parseInt(card.getAttribute("data-total-reviews")) || 0;
    const isHired = card.getAttribute("data-is-hired") === "1";
    const isHiredForThisAd = card.getAttribute("data-is-hired-for-this-ad") === "1";
    let buttonClass = "quick-apply";
    let buttonOnClick = `onclick="window.location.href='applytocompany.php?id=${internshipId}'"`;
    let buttonTitle = "";
    let buttonText = "Apply Now";
  
    // Determine button state
    if (loginMethod === 'google') {
      buttonClass += " disabled";
      buttonTitle = `title="Google login users cannot apply"`;
      buttonOnClick = "";
    } else if (isHiredForThisAd) {
      buttonClass += " disabled";
      buttonTitle = `title="You have been hired for this internship"`;
      buttonText = "Hired for this Internship";
      buttonOnClick = "";
    } else if (isHired) {
      buttonClass += " disabled";
      buttonTitle = `title="You are already hired for another internship"`;
      buttonText = "Already Hired Elsewhere";
      buttonOnClick = "";
    } else if (hasApplied) {
      buttonClass += " disabled";
      buttonTitle = `title="You have already applied"`;
      buttonText = "Already Applied";
      buttonOnClick = "";
    }
    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= totalRating) {
            starsHtml += '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ff9800"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
        } else {
            starsHtml += '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ccc"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
        }
    }


  jobOffers.innerHTML = `
      <div class="bgwidth">
      <div class="bgcolor">
      <div class="logo">
          <img src="../RECRUITER/${companyLogo}" alt="Company Logo">
      </div>

      <h1 class="tit">${internshipTitle}</h1>

        <div class="company">
            <span>${companyName}</span>
            <div class="rating">
                <div class="stars">
                    ${starsHtml}
                </div>
                <a href="#" class="review-link">${totalReviews} reviews</a>
                <span class="dot">•</span>
                <a href="#" class="review-link">View all internships</a>
            </div>
        </div>

      <div class="details">
          <div class="detail-item">
          <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#2E3849">
              <path d="M480-186q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 79q-14 0-28-5t-25-15q-65-60-115-117t-83.5-110.5q-33.5-53.5-51-103T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 45-17.5 94.5t-51 103Q698-301 648-244T533-127q-11 10-25 15t-28 5Zm0-453Zm0 80q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Z"/>
          </svg>
          <span>${companyAddress}</span>
          <button class="direction-button" onclick="window.location.href='DirectionMap.php?lat=${latitude}&lng=${longitude}&logo=${encodeURIComponent(companyLogo)}&name=${encodeURIComponent(companyName)}&address=${encodeURIComponent(companyAddress)}&contact=${encodeURIComponent(card.getAttribute('data-company-phone'))}&website=${encodeURIComponent(card.getAttribute('data-company-email'))}&id=${companyId}&internship_id=${internshipId}'">Direction<svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="white"><path d="m300-300 280-80 80-280-280 80-80 280Zm180-120q-25 0-42.5-17.5T420-480q0-25 17.5-42.5T480-540q25 0 42.5 17.5T540-480q0 25-17.5 42.5T480-420Zm0 340q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Zm0-320Z"/></svg></button>
          </div>
          
          <div class="detail-item">
          <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
              <path d="M520-496v-144q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v159q0 8 3 15.5t9 13.5l132 132q11 11 28 11t28-11q11-11 11-28t-11-28L520-496ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Z"/>
          </svg>
          <span>${internshipType}</span>
          <span class="dot">•</span>
          <span>${duration} hours</span>
          </div>

          <div class="detail-item">
          <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
              <path d="M80-200v-560q0-33 23.5-56.5T160-840h240q33 0 56.5 23.5T480-760v80h320q33 0 56.5 23.5T880-600v400q0 33-23.5 56.5T800-120H160q-33 0-56.5-23.5T80-200Zm80 0h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 480h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 480h320v-400H480v80h80v80h-80v80h80v80h-80v80Zm160-240v-80h80v80h-80Zm0 160v-80h80v80h-80Z"/>
          </svg>
          <span>${internshipTitle} (${department})</span>
          </div>

          <div class="detail-item">
          <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
              <path d="M500-482q29-32 44.5-73t15.5-85q0-44-15.5-85T500-798q60 8 100 53t40 105q0 60-40 105t-100 53Zm198 322q11-18 16.5-38.5T720-240v-40q0-36-16-68.5T662-406q51 18 94.5 46.5T800-280v40q0 33-23.5 56.5T720-160h-22Zm102-360h-40q-17 0-28.5-11.5T720-560q0-17 11.5-28.5T760-600h40v-40q0-17 11.5-28.5T840-680q17 0 28.5 11.5T880-640v40h40q17 0 28.5 11.5T960-560q0 17-11.5 28.5T920-520h-40v40q0 17-11.5 28.5T840-440q-17 0-28.5-11.5T800-480v-40Zm-480 40q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM0-240v-32q0-34 17.5-62.5T64-378q62-31 126-46.5T320-440q66 0 130 15.5T576-378q29 15 46.5 43.5T640-272v32q0 33-23.5 56.5T560-160H80q-33 0-56.5-23.5T0-240Zm320-320q33 0 56.5-23.5T400-640q0-33-23.5-56.5T320-720q-33 0-56.5 23.5T240-640q0 33 23.5 56.5T320-560ZM80-240h480v-32q0-11-5.5-20T540-306q-54-27-109-40.5T320-360q-56 0-111 13.5T100-306q-9 5-14.5 14T80-272v32Zm240-400Zm0 400Z"/>
          </svg>
          <span>${numberOfOpenings} available internship spots</span>
          </div>

          <div class="detail-item">
          <span style= "color: #666666;"">Posted ${postedTime}</span> 
          </div>
      </div>
        <div class="buttons">
          <button class="${buttonClass}" ${buttonOnClick} ${buttonTitle}>
            ${buttonText}
            <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="${buttonClass.includes('disabled') ? '#808080' : '#FFFFFF'}">
              <path d="M211-480q0 100.5 64.93 176.03 64.94 75.53 164.2 90.25 17.67 2.72 28.77 16.51 11.1 13.8 11.1 31.71 0 19.63-16.29 31.95-16.3 12.31-36.69 9.59-133.09-19.15-220.05-120.05Q120-344.91 120-480q0-134.33 86.59-235.23 86.58-100.9 219.43-120.81 21.15-2.96 37.57 9.09Q480-814.89 480-794.5q0 17.91-11.1 31.71-11.1 13.79-28.77 16.51-99.26 14.72-164.2 90.25Q211-580.5 211-480Zm462.61 45.5H400q-19.15 0-32.33-13.17Q354.5-460.85 354.5-480t13.17-32.33Q380.85-525.5 400-525.5h273.61l-65.68-65.67q-13.43-13.68-13.43-32.33t13.67-32.33Q621.85-669.5 640-669.5t31.83 13.67l144 143.76Q829.5-498.39 829.5-480t-13.67 32.07L672.07-304.17Q658.39-290.5 640-290.88q-18.39-.38-32.07-14.05-13.43-13.68-13.43-31.95t13.67-31.95l65.44-65.67Z"/>
            </svg>
          </button>
          <button class="save" onclick="window.location.href='studentfrontpageview.php?id=${internshipId}'">View Details</button>
        </div>
      </div>


      <div class="job-content">
      <h2>Company</h2>
      <p>${companyOverview}</p>
      </div>
      </div>
      
  `;
}


