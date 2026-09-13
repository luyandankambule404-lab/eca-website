<script>
// Sample data - replace this with your actual data
const contractors = [
  { name: "John Doe", company: "ABC Inc.", location: "New York" },
  { name: "Jane Smith", company: "XYZ Corp.", location: "Los Angeles" },
  // Add more contractor objects here
];

const tableBody = document.querySelector("#contractorsTable tbody");
const searchInput = document.querySelector("#searchInput");

function populateTable(data) {
  tableBody.innerHTML = "";
  data.forEach((contractor) => {
    const row = tableBody.insertRow();
    row.innerHTML = `
      <td>${contractor.name}</td>
      <td>${contractor.company}</td>
      <td>${contractor.location}</td>
    `;
  });
}

function filterTable(searchTerm) {
  const filteredData = contractors.filter((contractor) => {
    const fullName = ${contractor.name} ${contractor.company} ${contractor.location}.toLowerCase();
    return fullName.includes(searchTerm.toLowerCase());
  });
  populateTable(filteredData);
}

searchInput.addEventListener("input", (e) => {
  filterTable(e.target.value);
});

// Initial population of the table
populateTable(contractors);
</script>