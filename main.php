<?php

class ScholarAPI {
    private $apiKey;
    private $authorId;
    private $cacheFile;
    private $cacheDuration;

    public function __construct() {
        $this->apiKey = "626809aa2306ed9f692097aca4621a59a25521a982fe46c9fae2b87017ee79cb";
        $this->authorId = "f34uj7UAAAAJ";
        $this->cacheFile = 'scholar_cache.json'; // File to store cached data
        $this->cacheDuration = 5; // 30 days in seconds
    }

    public function getScholarData() {
        if ($this->isCacheValid()) {
            return $this->getCacheData();
        }

        $params = [
            "engine" => "google_scholar_author",
            "author_id" => $this->authorId,
            "hl" => "en",
            "num" => "100",
            "sort" => "pubdate",
            "api_key" => $this->apiKey
        ];

        $url = "https://serpapi.com/search?" . http_build_query($params);

        try {
            $response = file_get_contents($url);
            if ($response === FALSE) {
                throw new Exception("Unable to fetch data.");
            }

            $results = json_decode($response, true);
            $formattedData = [
                "profile" => $this->formatProfile($results),
                "publications" => $this->formatPublications($results)
            ];

            // Save data to cache
            $this->saveCacheData($formattedData);

            return $formattedData;
        } catch (Exception $e) {
            if ($this->isCacheValid()) {
                return $this->getCacheData();
            }

            return ["error" => $e->getMessage()];
        }
    }

    private function formatProfile($results) {
        $authorInfo = $results['author'] ?? [];
        $citedByTable = $results['cited_by']['table'] ?? [];

        $hIndex = 0;
        $i10Index = 0;

        foreach ($citedByTable as $item) {
            if (isset($item['h_index'])) {
                $hIndex = $item['h_index']['all'] ?? 0;
            }
            if (isset($item['i10_index'])) {
                $i10Index = $item['i10_index']['all'] ?? 0;
            }
        }

        return [
            "name" => $authorInfo['name'] ?? '',
            "affiliation" => $authorInfo['affiliations'] ?? '',
            "email" => $authorInfo['email'] ?? '',
            "citations" => [
                "total" => $citedByTable[0]['citations']['all'] ?? 0,
                "h_index" => $hIndex,
                "i10_index" => $i10Index
            ]
        ];
    }

    private function formatPublications($results) {
        $publications = [];
        foreach ($results['articles'] ?? [] as $article) {
            $publications[] = [
                "title" => $article['title'] ?? '',
                "authors" => $article['authors'] ?? '',
                "journal" => $article['publication'] ?? '',
                "year" => $article['year'] ?? '',
                "citations" => $article['cited_by']['value'] ?? 0,
                "link" => $article['link'] ?? ''
            ];
        }
        return $publications;
    }

    private function isCacheValid() {
        if (!file_exists($this->cacheFile)) {
            return false;
        }

        $cacheData = json_decode(file_get_contents($this->cacheFile), true);
        $timestamp = $cacheData['timestamp'] ?? 0;

        return (time() - $timestamp) < $this->cacheDuration;
    }

    private function getCacheData() {
        if (file_exists($this->cacheFile)) {
            $cacheData = json_decode(file_get_contents($this->cacheFile), true);
            return $cacheData['data'] ?? [];
        }
        return [];
    }

    private function saveCacheData($data) {
        $cacheData = [
            "timestamp" => time(),
            "data" => $data
        ];

        if (file_put_contents($this->cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT)) === false) {
            throw new Exception("Failed to save cache file.");
        }
    }
}

$scholarAPI = new ScholarAPI();
$publications = $scholarAPI->getScholarData()['publications'];
?>



<!doctype html>
<html class="no-js" lang="">

<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title></title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link rel="manifest" href="site.webmanifest">
  <link rel="apple-touch-icon" href="icon.png">
  <!-- Place favicon.ico in the root directory -->

  <!--<link rel="stylesheet" href="css/normalize.css">-->
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</head>
<style type="text/css">
  :root {
    --primary-color: #4a90e2;
    --secondary-color: #67c23a;
    --text-color: #333;
    --light-gray: #f5f5f5;
    --border-color: #ddd;
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  }

  body {
    background-color: #fff;
    color: var(--text-color);
    line-height: 1.6;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .profile-section {
    display: flex;
    gap: 2rem;
    margin-bottom: 3rem;
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .profile-image {
    width: 200px;
    height: 200px;
    object-fit: cover;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .profile-info {
    flex: 1;
    min-width: 300px;
  }

  .profile-info h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-color);
  }

  .profile-info h2 {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
  }

  .contact-info {
    margin-bottom: 1.5rem;
  }

  .contact-info a {
    color: var(--primary-color);
    text-decoration: none;
  }

  .interests {
    margin: 1rem 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .interest-tag {
    background: var(--light-gray);
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.9rem;
    color: var(--primary-color);
  }

  .social-links {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .social-links a {
    color: var(--text-color);
    text-decoration: none;
    font-size: 1.5rem;
    transition: color 0.3s;
  }

  .social-links a:hover {
    color: var(--primary-color);
  }

  .stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .stat-box {
    font-size: 20px;
    background: var(--light-gray);
    padding: 1rem;
    border-radius: 5px;
    text-align: center;
    flex: 1;
    min-width: 10px;
    transition: transform 0.3s;
  }

  .stat-box:hover {
    transform: translateY(-2px);
  }

  .stat-box h3 {
    font-size: 1.5rem;
    color: var(--primary-color);
  }

  .publications {
    margin-top: 3rem;
  }

  .filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .search-bar {
    flex: 1;
    min-width: 200px;
    padding: 0.8rem;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    font-size: 1rem;
  }

  .year-filter {
    padding: 0.8rem;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    min-width: 100px;
  }

  .publication-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: transform 0.2s;
  }

  .publication-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .publication-title {
    color: var(--primary-color);
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    text-decoration: none;
    display: block;
  }

  .publication-title:hover {
    text-decoration: underline;
  }

  .publication-authors {
    color: #666;
    margin-bottom: 0.5rem;
  }

  .publication-journal {
    color: #888;
    font-style: italic;
  }

  .publication-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
  }

  .publication-year {
    color: var(--secondary-color);
    font-weight: bold;
  }

  .citations {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
  }

  .loading {
    text-align: center;
    padding: 2rem;
    color: #666;
  }

  .error-message {
    color: #dc3545;
    padding: 1rem;
    text-align: center;
    background: #ffe6e6;
    border-radius: 5px;
    margin: 1rem 0;
  }

  @media (max-width: 768px) {
    .profile-section {
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .social-links {
      justify-content: center;
    }

    .stats {
      justify-content: center;
    }
  }

  .horizontal-list {
    list-style-type: disc;
    /* Keeps the bullet points */
    display: flex;
    /* Makes the list horizontal */
    gap: 1rem;
    /* Adds spacing between items */
    padding: 0;
    /* Removes default padding */
    margin: 0;
    /* Removes default margin */
  }

  .horizontal-list li {
    display: inline;
    /* Ensures list items are inline */
  }

  .accordian_style {
    width: 100%;
    text-transform: uppercase;
    font-size: 15px;
    font-weight: bolder;
  }
</style>

<body style="font-family: 'Roboto', sans-serif;">
  <!--[if lte IE 9]>
    <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
  <![endif]-->

  <!-- Add your site or application content here -->
  <div class="container">
    <div class="row">
      <div class="col-sm-8"><img src="img/banner.jpg" width="100%" height="120" /></div>
      <!--<div class="col-sm-4"></div>-->
      <div class="col-sm-4">
        <div class="row">
          <div class="col" style="margin-top: 10%;">
            <h5 style="margin-bottom: 1%;float: right">Mohammad Saud Afzal</h5>
            <p style="float: right;line-height: 80%;">
              <sub style="float: right">Associate Professor</sub><br />
              <sub style="float: right">Indian Institute of Technology</sub><br />
              <sub style="float: right">Kharagpur</sub>
            </p>
          </div>
          <div>
            <img src="img/saudAfzal.JPG" width="100" height="120" style="float: right" />
          </div>
        </div>
      </div>
    </div>
    <hr />
    <div class="row">
      <div class="col-sm-4" style="border-right: 1px solid;">
        <div class="list-group" id="list-tab" role="tablist">
          <a class="list-group-item list-group-item-action active" id="list-home-list" data-toggle="list"
            href="#list-home" role="tab" aria-controls="home">Home</a>
          <a class="list-group-item list-group-item-action" id="list-profile-list" data-toggle="list"
            href="#list-research" role="tab" aria-controls="research">Team</a>
          <a class="list-group-item list-group-item-action" id="list-messages-list" data-toggle="list"
            href="#list-projects" role="tab" aria-controls="projects">Research Projects</a>
          <a class="list-group-item list-group-item-action" id="list-settings-list" data-toggle="list"
            href="#list-publications" role="tab" aria-controls="publications">Chatbot - CourseMate</a>
          <a class="list-group-item list-group-item-action" id="list-settings-list" data-toggle="list"
            href="#list-teaching" role="tab" aria-controls="teaching">Instructed Courses</a>
          <a class="list-group-item list-group-item-action" id="list-settings-list" data-toggle="list"
            href="#list-consultancy" role="tab" aria-controls="consultancy">Consultancy</a>
          <a class="list-group-item list-group-item-action" id="list-settings-list" data-toggle="list"
            href="#list-students" role="tab" aria-controls="students">Alumni Members</a>
          <a class="list-group-item list-group-item-action" id="list-settings-list" data-toggle="list"
            href="#list-personal" role="tab" aria-controls="personal">Contact & Resources</a>
          <a class="list-group-item list-group-item-action" id="list-settings-list" data-toggle="list"
            href="#list-links" role="tab" aria-controls="links">About Us</a>
        </div>
      </div>
      <div class="col-sm-8">
        <div class="tab-content" id="nav-tabContent">
          <div class="tab-pane fade show active" id="list-home" role="tabpanel" aria-labelledby="list-home-list">
            <div class="container">
              <div class="profile-section">
                <img src="img/sir-image.png" alt="Mohammad Saud Afzal" class="profile-image"
                  style="width: 150px; height: 150px;">

                <div class="profile-info">
                  <h2 id="scholar-name">Mohammad Saud Afzal</h1>
                    <h6 id="scholar-affiliation">Indian Institute of Technology Kharagpur
                  </h2>

                  <div class="contact-info">
                    <p><a id="scholar-email" href="#"></a></p>
                  </div>

                  <div class="interests" id="scholar-interests">
                    <!-- Interests will be populated by JavaScript -->
                    <ul class="horizontal-list">
                      <li>Computational Fluid Dynamics</li>
                      <li>Coastal Engineering</li>
                      <li>Hydraulic Engineering</li>
                      <li>Ocean Engineering</li>
                    </ul>
                  </div>

                  <div class="social-links">
                    <a href="https://scholar.google.com/citations?user=f34uj7UAAAAJ&hl=en" title="Google Scholar"
                      id="scholar-profile"><i class="fab fa-google"></i></a>
                    <a href="https://www.researchgate.net/profile/Mohammad-Saud-Afzal" title="Research Gate"><i
                        class="fas fa-flask"></i></a>
                    <a href="http://www.facweb.iitkgp.ac.in/~saud/" title="Personal Website"><i
                        class="fas fa-globe"></i></a>
                    <a href="mailto:saud@civil.iitkgp.ac.in" title="Email"><i class="fas fa-envelope"></i></a>
                  </div>

                  <div class="stats" id="citation-stats">
                    <!-- Stats will be populated by JavaScript -->
                  </div>
                </div>
              </div>

              <div class="publications">
                <div class="filters">
                  <input type="text" class="search-bar"
                    placeholder="Search publications by title, authors, or journal...">
                  <select class="year-filter" id="year-filter">
                    <option value="">All Years</option>
                    <!-- Years will be populated by JavaScript -->
                  </select>
                </div>

                <div class="publication-list">
                  <!-- Publications will be populated by JavaScript -->
                </div>
              </div>
            </div>

            <script>
              // Utility functions
              function formatNumber(num) {
                return new Intl.NumberFormat().format(num);
              }

              // Load scholar data
              async function loadScholarData() {
                try {

                  const data = <?php echo json_encode($scholarAPI->getScholarData()['profile']); ?>;

                  // Update profile information
                  document.getElementById('scholar-name').textContent = data.name;
                  document.getElementById('scholar-affiliation').textContent = data.affiliation;
                  document.getElementById('scholar-email').textContent = "saud@civil.iitkgp.ac.in";
                  document.getElementById('scholar-email').href = `mailto:${data.email}`;

                  const statsContainer = document.getElementById('citation-stats');
                  statsContainer.innerHTML = `
                    <div class="stat-box">
                        <h3>${formatNumber(data.citations.total)}</h3>
                        <p>Citations</p>
                    </div>
                    <div class="stat-box">
                        <h3>${data.citations.h_index}</h3>
                        <p>h-index</p>
                    </div>
                    <div class="stat-box">
                        <h3>${data.citations.i10_index}</h3>
                        <p>i10-index</p>
                    </div>
                `;
                } catch (error) {
                  console.error('Error loading scholar data:', error);
                  showError('Failed to load scholar profile data');
                }
              }

              // Declare debounceTimer at the top level
              let debounceTimer;
              let publicationsData = []; // Global variable to store all publications data

              // Debounce function to delay execution
              function debounce(func, delay) {
                  return function (...args) {
                      clearTimeout(debounceTimer);
                      debounceTimer = setTimeout(() => func.apply(this, args), delay);
                  };
              }

              async function loadPublications(searchTerm = '', yearFilter = '') {
                  console.log('Search Term:', searchTerm);
                  console.log('Year Filter:', yearFilter);

                  const publicationList = document.querySelector('.publication-list');
                  publicationList.innerHTML = '<div class="loading">Loading publications...</div>';

                  try {
                      // Load data if not already loaded
                      if (publicationsData.length === 0) {
                          const response = await fetch('scholar_cache.json');
                          if (!response.ok) throw new Error('Failed to fetch JSON file');
                          const data = await response.json();
                          publicationsData = data.data?.publications || [];
                      }

                      // Filter publications
                      const filteredPublications = publicationsData.filter(pub => {
                          const matchesSearch = !searchTerm || 
                              pub.title?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                              pub.authors?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                              pub.journal?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                              String(pub.year).includes(searchTerm);

                          const matchesYear = !yearFilter || String(pub.year) === yearFilter;

                          return matchesSearch && matchesYear;
                      });

                      console.log('Filtered Publications:', filteredPublications);

                      // Update the display
                      if (filteredPublications.length === 0) {
                          publicationList.innerHTML = '<div class="no-results">No publications found</div>';
                          return;
                      }

                      updateYearFilterOptions(publicationsData);
                      publicationList.innerHTML = filteredPublications
                          .map(pub => createPublicationCard(pub))
                          .join('');

                  } catch (error) {
                      console.error('Error loading publications:', error);
                      showError('Failed to load publications');
                  }
              }

              // Wait for DOM to be fully loaded
              document.addEventListener('DOMContentLoaded', function() {
                  // Initialize the search functionality
                  const searchBar = document.querySelector('.search-bar');
                  const yearFilter = document.getElementById('year-filter');

                  if (searchBar) {
                      const debouncedSearch = debounce((searchTerm, yearValue) => {
                          loadPublications(searchTerm, yearValue);
                      }, 300);

                      searchBar.addEventListener('input', (e) => {
                          const searchTerm = e.target.value;
                          const yearValue = yearFilter ? yearFilter.value : '';
                          debouncedSearch(searchTerm, yearValue);
                      });
                  } else {
                      console.error('Search bar element not found');
                  }

                  if (yearFilter) {
                      yearFilter.addEventListener('change', (e) => {
                          const searchTerm = searchBar ? searchBar.value : '';
                          const yearValue = e.target.value;
                          loadPublications(searchTerm, yearValue);
                      });
                  }
              });



              function createPublicationCard(pub) {
                  return `
                      <div class="publication-card">
                          <a href="${pub.link}" class="publication-title" target="_blank">${pub.title}</a>
                          <p class="publication-authors">${pub.authors}</p>
                          <p class="publication-journal">${pub.journal}</p>
                          <div class="publication-meta">
                              <span class="publication-year">${pub.year}</span>
                              ${pub.citations > 0
                                  ? `<span class="citations">
                                      <i class="fas fa-quote-right"></i>
                                      ${formatNumber(pub.citations)} citations
                                  </span>`
                                  : ''}
                          </div>
                      </div>
                  `;
              }

              function updateYearFilterOptions(publications) {
                  const yearFilter = document.getElementById('year-filter');
                  const years = [...new Set(publications.map(pub => pub.year))].sort((a, b) => b - a);

                  const currentValue = yearFilter.value;
                  yearFilter.innerHTML = '<option value="">All Years</option>' +
                      years.map(year => `<option value="${year}">${year}</option>`).join('');
                  yearFilter.value = currentValue;
              }

              function showError(message) {
                  const errorDiv = document.createElement('div');
                  errorDiv.className = 'error-message';
                  errorDiv.textContent = message;
                  document.querySelector('.publication-list').prepend(errorDiv);
              }


              document.querySelector('.search-bar').addEventListener('input', debounce((e) => {
                  const searchTerm = e.target.value; // Get the current value of the search bar
                  const yearFilter = document.getElementById('year-filter').value; // Get the selected year filter

                  // Reload publications based on search term and year filter
                  loadPublications(searchTerm, yearFilter);
              }, 600)); // Debounce to prevent excessive filtering


              document.getElementById('year-filter').addEventListener('change', (e) => {
                  const searchTerm = document.querySelector('.search-bar').value;
                  const yearFilter = e.target.value;
                  loadPublications(searchTerm, yearFilter);
              });

              // Initial load
              loadScholarData();
              loadPublications();

            </script>
          </div>


          <div class="tab-pane fade" id="list-research" role="tabpanel" aria-labelledby="list-profile-list">
            <p style="font-size: 20px;font-weight: bolder;text-align: center">GROUP LEADER</p>
            <hr />
            <div class="card" style="width: 19rem;margin: auto">
              <img class="card-img-top" src="img/saudAfzal.JPG" style="margin: auto;width: 130px">
              <div class="card-body">
                <div class="col" style="text-align: center;">
                  <h5 style="margin-bottom: 1%;">Mohammad Saud Afzal</h5>
                  <p style="line-height: 80%;">
                    <sub>Associate Professor</sub><br />
                    <sub>Indian Institute of Technology,Kharagpur</sub><br />
                    <sub>saud@civil.iitkgp.ac.in</sub>
                  </p>
                </div>
              </div>
            </div>
            <!-- add a new section-->
            <hr />
            <div>
              <p style="font-size: 20px;font-weight: bolder;text-align: center">PHD STUDENTS AND RESEARCHERS</p>
            </div>
            <hr />
            <br />
            <div class="container">
              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/gazi.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Ainal Hoque Gazi</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->
                <!--column 2 start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/debasish.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Debasish Dutta</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column 2 end-->
              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->

              <br />

              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/lalit.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Lalit Kumar</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Experimental Hydraulics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->
                <!--column 2 start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/subhrangshu.png" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Subhrangshu Purkayastha</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column 2 end-->
              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->

              <br />

              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/arijit.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Arijit Pradhan</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->
                <!--column 2 start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/jeet.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Jitendra Kumar</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column 2 end-->
              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->

              <br />

              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/akhsanul.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Mohd. Akhsanul Islam</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Arctic Coastal Erosion</sub><br />
                        <sub>NTNU, Norway</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->
                <!--column 2 start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/jianxun.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Jianxun Zhu</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>NTNU, Norway</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column 2 end-->
              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->
            </div>
            <!--section end-->





            <!-- add a new section-->
            <hr />
            <div>
              <p style="font-size: 20px;font-weight: bolder;text-align: center">M.TECH STUDENTS</p>
            </div>
            <hr />
            <br />
            <div class="container">
              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/suman.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Suman Kumar Mallick</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->
                <!--column 2 start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/shiv.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Shivshankar Chilwad</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Numerical Modelling</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column 2 end-->
              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->

              <br />

              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/shekhar.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Shekhar Shailendra Gautam</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Computational Fluid Dynamics</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->

              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->
            </div>
            <!--section end-->

            <!-- add a new section-->
            <hr />
            <div>
              <p style="font-size: 20px;font-weight: bolder;text-align: center">B.TECH STUDENTS</p>
            </div>
            <hr />
            <br />
            <div class="container">
              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/vikram.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Vikram Chugh</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Machine Learning</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->
                <!--column 2 start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/yogesh.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Yogesh Kumar</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Machine Learning</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column 2 end-->
              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->

              <br />

              <!----Copy from here---->
              <div class="row" style="margin-bottom: 5px;margin-left: 5px;">
                <!--column one start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/ashish.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Ashishsingh Bharatbhai Solanki</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Arctic Coastal Erosion</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column one end-->
                <!--column 2 start-->
                <div style="width:50%">
                  <div class="row">
                    <div>
                      <img src="img/sourangshu.JPG" width="100" height="120" />
                    </div>
                    <div class="col">
                      <h5 style="margin-bottom: 1%;">Sourangshu Ghosh</h5>
                      <p style="line-height: 80%;">
                        <sub>Research : Fluid Structure Interaction</sub><br />
                        <sub>Indian Institute of Technology, Kharagpur</sub><br />
                        <sub>Email : </sub>
                      </p>
                    </div>
                  </div>
                </div>
                <!--column 2 end-->
              </div>
              <!--till here to add new row having 2 columns make sure to add a <br/> to introduce a new line-->
            </div>
            <!--section end-->


          </div>
          <div class="tab-pane fade" id="list-projects" role="tabpanel" aria-labelledby="list-messages-list">
            <div id="accordion">
              <div class="card">
                <div class="card-header" id="headingOne">
                  <h5 class="mb-0">
                    <button class="btn btn-link accordian_style" data-toggle="collapse" data-target="#collapseOne"
                      aria-expanded="true" aria-controls="collapseOne">
                      Ongoing Projects
                    </button>
                  </h5>
                </div>

                <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                  <div class="card-body">
                    <ul class="list-group">
                      <li class="list-group-item">Computational Fluid Dynamics modelling of hydrodynamics and scour
                        around coastal structures <strong>Science and Engineering Research Board (SERB)</strong></li>
                      <li class="list-group-item">Flow over geosynthetic gabion weir <strong>Science and Engineering
                          Research Board (SERB)</strong></li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingTwo">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed accordian_style" data-toggle="collapse"
                      data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                      Completed Projects
                    </button>
                  </h5>
                </div>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                  <div class="card-body">
                    <div class="card-body">
                      <ul class="list-group">
                        <li class="list-group-item">Predictive Tool for Arctic Coastal Hydrodynamics and Sediment
                          Transport <strong>National Centre for Polar and Ocean Research</strong></li>
                        <li class="list-group-item">Large Scale Computational Fluid Dynamics Modelling of the
                          Hydrodynamics and Scour around Offshore Wind Farms <strong>Science and Engineering Research
                            Board (SERB)</strong></li>
                        <li class="list-group-item">3D Computational Fluid Dynamics Modeling of the Hydrodynamics and
                          Local Scour Around Offshore Structures Under Combined Action of Current and Waves
                          <strong>ISIRD, SRIC</strong></li>
                      </ul>
                    </div>

                  </div>
                </div>
              </div>
            </div>

          </div>
          <div class="tab-pane fade" id="list-publications" role="tabpanel" aria-labelledby="list-settings-list">
            <p style="font-size: 1.4rem; font-weight: bold;">
              <a href="https://ai-hydraulics.streamlit.app/" target="_blank"
                style="color: green; text-decoration: underline;">Hydraulic Engineering</a>
            </p>



          </div>
          <div class="tab-pane fade" id="list-teaching" role="tabpanel" aria-labelledby="list-settings-list">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th scope="col">Course Name</th>
                  <th scope="col">Semester</th>
                  <th scope="col">Year</th>
                  <th scope="col">Institution</th>
                  <th scope="col">Feedback (Out Of 5)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th scope="row">Hydraulics</th>
                  <td>Spring</td>
                  <td>2020</td>
                  <td>IIT Kharagpur</td>
                  <td>Ongoing</td>
                </tr>
                <tr>
                  <th scope="row">Hydraulics and Water Resources Engineering Lab</th>
                  <td>Spring</td>
                  <td>2020</td>
                  <td>IIT Kharagpur</td>
                  <td>Ongoing</td>
                </tr>
                <tr>
                  <th scope="row">Viscous Fluid Flow</th>
                  <td>Autumn</td>
                  <td>2019</td>
                  <td>IIT Kharagpur</td>
                  <td>4.25</td>
                </tr>
                <tr>
                  <th scope="row">Engineering Drawing and Computer Graphics</th>
                  <td>Autumn</td>
                  <td>2019</td>
                  <td>IIT Kharagpur</td>
                  <td>3.97</td>
                </tr>
                <tr>
                  <th scope="row">Hydraulics</th>
                  <td>Spring</td>
                  <td>2019</td>
                  <td>IIT Kharagpur</td>
                  <td>4.35</td>
                </tr>
                <tr>
                  <th scope="row">Hydraulics and Water Resources Engineering Lab</th>
                  <td>Spring</td>
                  <td>2019</td>
                  <td>IIT Kharagpur</td>
                  <td>3.90</td>
                </tr>
                <tr>
                  <th scope="row">Viscous Fluid Flow</th>
                  <td>Autumn</td>
                  <td>2018</td>
                  <td>IIT Kharagpur</td>
                  <td>3.90</td>
                </tr>
                <tr>
                  <th scope="row">Computer Applications in Free Surface Flows and Hydrology</th>
                  <td>Autumn</td>
                  <td>2018</td>
                  <td>IIT Kharagpur</td>
                  <td>3.86</td>
                </tr>
                <tr>
                  <th scope="row">Engineering Drawing and Computer Graphics</th>
                  <td>Autumn</td>
                  <td>2018</td>
                  <td>IIT Kharagpur</td>
                  <td>3.92</td>
                </tr>
                <tr>
                  <th scope="row">Hydraulics</th>
                  <td>Spring</td>
                  <td>2018</td>
                  <td>IIT Kharagpur</td>
                  <td>4.44</td>
                </tr>
                <tr>
                  <th scope="row">Turbulent Fluid Flow</th>
                  <td>Spring</td>
                  <td>2018</td>
                  <td>IIT Kharagpur</td>
                  <td>4.43</td>
                </tr>
                <tr>
                  <th scope="row">Engineering Drawing and Computer Graphics</th>
                  <td>Spring</td>
                  <td>2018</td>
                  <td>IIT Kharagpur</td>
                  <td>3.97</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="tab-pane fade" id="list-consultancy" role="tabpanel" aria-labelledby="list-consultancy-list">
            <p style="font-size: 20px;font-weight: bolder;">ANNOUNCEMENTS</p>
            <hr />
            <ul class="list-group">
              <center>
                <img src="img/consultancy.png" alt="CORE CFD Group Consultancy" width="500">
              </center>
              <br>
              <div style="text-align: center;">
                <img src="img/consultancy_d1.png" alt="Image 1" style="display: inline-block; width: 48%;">
                <img src="img/consultancy_d2.png" alt="Image 2" style="display: inline-block; width: 48%;">
              </div>
              <div style="text-align: center;">
                <img src="img/consultancy_d3.png" alt="Image 3" style="display: inline-block; width: 48%;">
                <img src="img/consultancy_d4.png" alt="Image 4" style="display: inline-block; width: 48%;">
              </div>


              <li class="list-group-item" "text-align: justify;">The <strong>CORE CFD Group, under the mentorship and
                  management of Prof. Mohammad Saud Afzal</strong>, offers comprehensive consultancy services
                specializing in advanced numerical modeling, field investigation, and experimental modeling. Their
                expertise includes numerical simulations for open channel flow, coastal wave modeling, and scour
                analysis around submerged structures using state-of-the-art tools like REEF3D. The group also provides
                field investigation services such as bathymetry surveys and oceanographic data collection. Additionally,
                their consultancy extends to experimental modeling, including 3D river model studies and the design of
                hydraulic structures, positioning the CORE CFD Group as a leading consultancy in the field of civil
                engineering.</li>
              <br>
              <li class="list-group-item">One opening for a Project Associate at IIT Kharagpur wishing to work in the
                field of <strong>hydraulics and coastal engineering</strong></li>
            </ul>

          </div>

          <div class="tab-pane fade" id="list-students" role="tabpanel" aria-labelledby="list-settings-list">

            <div id="accordion">
              <div class="card">
                <div class="card-header" id="headingOne">
                  <h5 class="mb-0">
                    <button class="btn btn-link accordian_style" data-toggle="collapse" data-target="#collapseOne"
                      aria-expanded="true" aria-controls="collapseOne">
                      PHD Supervision
                    </button>
                  </h5>
                </div>

                <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                  <div class="card-body">
                    <table class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th scope="col">Name</th>
                          <th scope="col">University</th>
                          <th scope="col">Thesis Topic</th>
                          <th scope="col">Year</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!--<tr>
                          <td>Sanjay</td>
                          <td>IIT Kharagpur</td>
                          <td>
                            <p>Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems</p>
                            <p>
                              Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems
                            </p>
                          </td>
                          <td>2019</td>
                        </tr>
                        <tr>
                          <td>Sanjay</td>
                          <td>Norwegian university of science and technology, Trondhiem</td>
                          <td>
                            <p>Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems</p>
                            <p>
                              Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems
                            </p>
                          </td>
                          <td>2019</td>
                        </tr>-->
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header" id="headingTwo">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed accordian_style" data-toggle="collapse"
                      data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                      M.TECH/MS Supervision
                    </button>
                  </h5>
                </div>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                  <div class="card-body">
                    <table class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th scope="col">Name</th>
                          <th scope="col">University</th>
                          <th scope="col">Thesis Topic</th>
                          <th scope="col">Year</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!--<tr>
                          <td>Sanjay</td>
                          <td>IIT Kharagpur</td>
                          <td>
                            <p>Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems</p>
                            <p>
                              Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems
                            </p>
                          </td>
                          <td>2019</td>
                        </tr>-->
                        <tr>
                          <td>Prashant Rathore</td>
                          <td>IIT Kharagpur</td>
                          <td>Experimental and CFD analysis of Dam Break</td>
                          <td>2019</td>
                        </tr>
                        <tr>
                          <td>Said Alhaddad</td>
                          <td>NTNU, Norway</td>
                          <td>Sedimentation study of Gaza Port</td>
                          <td>2016</td>
                        </tr>
                        <tr>
                          <td>Benedicte T Borgersen</td>
                          <td>NTNU, Norway</td>
                          <td>Artic Erosion</td>
                          <td>2016</td>
                        </tr>
                        <tr>
                          <td>Hubert Konkol</td>
                          <td>NTNU, Norway</td>
                          <td>Wave Modelling using MIKE21 SW and BW</td>
                          <td>2016</td>
                        </tr>
                        <tr>
                          <td>Wiktor Mateusz Wickland</td>
                          <td>NTNU, Norway</td>
                          <td>Wave Modelling using MIKE21 SW and BW</td>
                          <td>2016</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed accordian_style" data-toggle="collapse"
                      data-target="#collapseThree" aria-expanded="false" aria-controls="collapseTwo">
                      B.TECH Thesis
                    </button>
                  </h5>
                </div>
                <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                  <div class="card-body">
                    <table class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th scope="col">Name</th>
                          <th scope="col">University</th>
                          <th scope="col">Thesis Topic</th>
                          <th scope="col">Year</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>Sandeep Kumar Das</td>
                          <td>IIT Kharagpur</td>
                          <td> 3D Numerical Modelling of Hydrodynamics aorund a slender structure </td>
                          <td>2019</td>
                        </tr>
                        <!--<tr>
                          <td>Sanjay</td>
                          <td>Norwegian university of science and technology, Trondhiem</td>
                          <td>
                            <p>Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems</p>
                            <p>
                              Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems
                            </p>
                          </td>
                          <td>2019</td>
                        </tr>-->
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingFour">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed accordian_style" data-toggle="collapse"
                      data-target="#collapseFour" aria-expanded="false" aria-controls="collapseTwo">
                      Project & Other Supervisions
                    </button>
                  </h5>
                </div>
                <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
                  <div class="card-body">
                    <table class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th scope="col">Name</th>
                          <th scope="col">University</th>
                          <th scope="col">Type</th>
                          <th scope="col">Topic</th>
                          <th scope="col">Year</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!--<tr>
                          <td>Sanjay</td>
                          <td>IIT Kharagpur</td>
                          <td>
                            <p>Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems</p>
                            <p>
                              Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems
                            </p>
                          </td>
                          <td>2019</td>
                        </tr>-->
                        <tr>
                          <td>Amrita Mandal</td>
                          <td>Jadavpur University</td>
                          <td>Summer Internship</td>
                          <td>Application of Machine Learning to flow over wiers</td>
                          <td>2019</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>



          </div>
          <div class="tab-pane fade" id="list-links" role="tabpanel" aria-labelledby="list-settings-list">
            Based at Indian Institute of Technology Kharagpur and led by Prof. Mohammad Saud Afzal, the members of the
            Lab use state of the art numerical tools to study Coastal, Ocean and River hydraulics related problems
          </div>
          <div class="tab-pane fade" id="list-personal" role="tabpanel" aria-labelledby="list-settings-list">
            <div class="list-group" id="attributeList">
              <a href="#" class="list-group-item list-group-item-action"
                onclick="gotoAttributeDetails('contact')">Contact Information</a>
              <a href="#" class="list-group-item list-group-item-action"
                onclick="gotoAttributeDetails('rInterest')">Research Interests</a>
              <a href="#" class="list-group-item list-group-item-action"
                onclick="gotoAttributeDetails('education')">Education</a>
              <a href="#" class="list-group-item list-group-item-action"
                onclick="gotoAttributeDetails('resources')">Resources</a>
            </div>
            <div id="panelDisplay">
              <!--------------------->
              <div id="contact" class="dList">
                <div class="card">
                  <div class="card-header"><strong>Contact Information</strong></div>
                  <div class="card-body">
                    <p class="card-text text-center">
                      <span>Indian Institute of Technology<br /> Kharagpur
                        Kharagpur, West Bengal - 721302</span><br />
                      <span>Phone : +917236886666</span><br />
                      <span>Email : saud@civil.iitkgp.ac.in</span>
                    </p>
                    <a href="#" class="btn btn-primary float-right" onclick="gotoAttribute('contact')">Back</a>
                  </div>
                </div>
              </div>
              <!--------------------->
              <div id="rInterest" class="dList">
                <div class="card">
                  <div class="card-header"><strong>Research Interests</strong></div>
                  <div class="card-body">
                    <div class="list-group">
                      <a href="#" class="list-group-item list-group-item-action">Numerical modeling</a>
                      <a href="#" class="list-group-item list-group-item-action">OffshoreWind Energy</a>
                      <a href="#" class="list-group-item list-group-item-action">Waves</a>
                      <a href="#" class="list-group-item list-group-item-action">Hydrodynamics</a>
                      <a href="#" class="list-group-item list-group-item-action">Sediment Transport</a>
                      <a href="#" class="list-group-item list-group-item-action">Computational Fluid Dynamics</a>
                      <a href="#" class="list-group-item list-group-item-action">Port Planning</a>
                      <a href="#" class="list-group-item list-group-item-action">Education</a>
                      <a href="#" class="list-group-item list-group-item-action">Design of coastal structures</a>
                      <a href="#" class="list-group-item list-group-item-action">Coastal Engineering in Arctic Areas</a>
                    </div>
                    <a href="#" class="btn btn-primary float-right" onclick="gotoAttribute('rInterest')">Back</a>
                  </div>
                </div>
              </div>
              <!--------------------->
              <div id="education" class="dList">
                <div class="card">
                  <div class="card-header"><strong>Education</strong></div>
                  <div class="card-body">
                    <div class="list-group list-group-flush">
                      <a href="#" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-between">
                          <h5 class="mb-1"><strong>Norwegian University of Science and Technology</strong></h5>
                          <small>Trondheim, Norway</small>
                        </div>
                        <ul class="list-group list-group-flush">
                          <li class="list-group-item">PhD., Department of Marine Technology, Sept 2013-March 2017
                            <ul>
                              <li>Title: Three-dimensional streaming in seabed boundary layer</li>
                              <li>Advisor: Dag Myrhaug, Professor and Lars Erik Holmedal, Professor</li>
                            </ul>
                          </li>
                          <li class="list-group-item">M.S., Coastal and Marine Engineering and Management, June 2013
                            <ul>
                              <li>MSc Thesis: 3D Numerical Modeling of Sediment Transport under combined current and
                                waves</li>
                              <li>Advisor: Øivind A. Arntsen, Associate Professor</li>
                              <li>Average Grade: A</li>
                            </ul>
                          </li>
                        </ul>
                      </a>
                      <a href="#" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-between">
                          <h5 class="mb-1"><strong>Indian Institute of Technology</strong></h5>
                          <small class="text-muted">Kanpur, India</small>
                        </div>
                        <ul class="list-group list-group-flush">
                          <li class="list-group-item">B.Tech., Civil Engineering , July 2008</li>
                        </ul>
                      </a>
                    </div>
                    <a href="#" class="btn btn-primary float-right" onclick="gotoAttribute('education')">Back</a>
                  </div>
                </div>
              </div>
              <!--------------------->
              <div id="resources" class="dList">
                <div class="card">
                  <div class="card-header"><strong>Resources</strong></div>
                  <div class="card-body">

                    <div id="accordion">
                      <div class="card">
                        <div class="card-header" id="headingOne">
                          <h5 class="mb-0">
                            <button class="btn btn-link accordian_style" data-toggle="collapse"
                              data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                              hydraulics
                            </button>
                          </h5>
                        </div>

                        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                          data-parent="#accordion">
                          <div class="card-body">
                            <div class="list-group">
                              <a href="resources/hydraulics/schedule2020.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Course Schedule 2020 Spring<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Lecture_Week_1.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 1 Lecture<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Assignment_1.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Assignment 1<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Lecture_Week_2.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 2 Lecture<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Assignment_2.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Assignment 2<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Lecture_Week_3.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 3 Lecture<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Assignment_3.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Assignment 3<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Lecture_Week_4.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 4 Lecture<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Assignment_4.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Assignment 4<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Lecture_Week_5.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 5 Lecture<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Assignment_5.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Assignment 5<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Week_6_Dimensional_Analysis.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 6 Lecture<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Assignment_6.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Assignment 6<span
                                  class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/week_7.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 7 Lecture : Uniform
                                Flow<span class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Week_8.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 8 Lecture : Non-Uniform
                                Flow<span class="badge badge-primary" style="float: right">Download</span></a>
                              <a href="resources/hydraulics/Week_9n10.pdf" target="_blank"
                                class="list-group-item list-group-item-action" download>Week 9 and 10 Lecture : Pipe
                                Flow<span class="badge badge-primary" style="float: right">Download</span></a>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="card">
                        <div class="card-header" id="headingTwo">
                          <h5 class="mb-0">
                            <button class="btn btn-link collapsed accordian_style" data-toggle="collapse"
                              data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                              openfoam
                            </button>
                          </h5>
                        </div>
                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                          <div class="card-body">
                            <div class="list-group">
                              <!--<a href="resources/hydraulics/test.pdf" target="_blank" class="list-group-item list-group-item-action" download>Numerical modeling<span class="badge badge-primary" style="float: right">Download</span></a>-->
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="card">
                        <div class="card-header" id="headingThree">
                          <h5 class="mb-0">
                            <button class="btn btn-link collapsed accordian_style" data-toggle="collapse"
                              data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                              reef3d
                            </button>
                          </h5>
                        </div>
                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                          data-parent="#accordion">
                          <div class="card-body">
                            <div class="list-group">
                              <!--<a href="resources/hydraulics/test.pdf" target="_blank" class="list-group-item list-group-item-action" download>Numerical modeling<span class="badge badge-primary" style="float: right">Download</span></a>-->
                            </div>
                          </div>
                        </div>
                      </div>

                      <!--<div class="card">
                        <div class="card-header" id="headingFour">
                          <h5 class="mb-0">
                            <button class="btn btn-link collapsed accordian_style" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                              reef3d
                            </button>
                          </h5>
                        </div>
                        <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
                          <div class="card-body">
                            <div class="list-group">
                              <a href="resources/hydraulics/test.pdf" target="_blank" class="list-group-item list-group-item-action" download>Numerical modeling<span class="badge badge-primary" style="float: right">Download</span></a>
                            </div>
                          </div>
                        </div>
                      </div>-->

                      <!--<div class="card">
                        <div class="card-header" id="headingFive">
                          <h5 class="mb-0">
                            <button class="btn btn-link collapsed accordian_style" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                              reef3d
                            </button>
                          </h5>
                        </div>
                        <div id="collapseFive" class="collapse" aria-labelledby="headingFive" data-parent="#accordion">
                          <div class="card-body">
                            <div class="list-group">
                              <a href="resources/hydraulics/test.pdf" target="_blank" class="list-group-item list-group-item-action" download>Numerical modeling<span class="badge badge-primary" style="float: right">Download</span></a>
                            </div>
                          </div>
                        </div>
                      </div>-->

                    </div>





                    <a href="#" class="btn btn-primary float-right" onclick="gotoAttribute('resources')">Back</a>
                  </div>
                </div>
              </div>
              <!--------------------->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery library -->
  <script src="js/jquery.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>

  <!-- Popper JS -->
  <script src="js/popper.min.js"></script>

  <!-- Latest compiled JavaScript -->
  <script src="js/bootstrap.min.js"></script>
  <script src="js/plugins.js"></script>
  <script src="js/main.js"></script>
  <script type="text/javascript">
    $(document).ready(function () {
      $('.table').DataTable({
        paging: false,
        searching: false,
      });
    });
  </script>
</body>

</html>
