// Store all matches globally for grouping
    let allMatches = [];

    // Fetch standings data
    async function fetchStandings() {
      try {
        const response = await fetch(
          "https://api.sports.gracenote.com/gsd/lookup/v1/leagues/GNA5MGHG2ZVX2RD/standings?language=pt-BR&api_key=e7zxsg4hhbfe4cg52bqa2pxs"
        );
        if (!response.ok) throw new Error("Falha ao buscar classificação");
        const data = await response.json();
        return data.standings;
      } catch (error) {
        console.error("Erro ao buscar classificação:", error);
        return [];
      }
    }

    // Fetch matches data with pagination
    async function fetchMatches() {
      try {
        const allMatches = [];
        const startDate = new Date('2026-06-11T19:00:00Z');
        const endDate = new Date('2026-07-19T23:59:59Z');
        const windowDays = 5;

        let currentStart = new Date(startDate);

        while (currentStart < endDate) {
          let currentEnd = new Date(currentStart);
          currentEnd.setDate(currentEnd.getDate() + windowDays);

          if (currentEnd > endDate) {
            currentEnd = new Date(endDate);
          }

          const timeFrom = currentStart.toISOString();
          const timeTo = currentEnd.toISOString();

          const response = await fetch(
            `https://api.sports.gracenote.com/gsd/lookup/v1/leagues/GNA5MGHG2ZVX2RD/schedule-results?timeFrom=${timeFrom}&timeTo=${timeTo}&language=pt-BR&showXids=true&api_key=e7zxsg4hhbfe4cg52bqa2pxs`
          );

          if (!response.ok) throw new Error("Falha ao buscar jogos");
          const data = await response.json();
          const matches = data.matches || [];
          allMatches.push(...matches);

          currentStart = new Date(currentEnd);
          currentStart.setSeconds(currentStart.getSeconds() + 1);
        }

        return allMatches;
      } catch (error) {
        console.error("Erro ao buscar jogos:", error);
        return [];
      }
    }

    // Group matches by their group phase
    function groupMatchesByGroup(matches) {
      const grouped = {};

      matches.forEach(match => {
        const groupPhase = match.relatedSportsEvents?.find(e => e.typeDetail === 'PHASE_GROUP');
        if (groupPhase) {
          const groupId = groupPhase.id;
          if (!grouped[groupId]) {
            grouped[groupId] = {
              id: groupId,
              name: groupPhase.navigationInfo?.name || 'Grupo',
              matches: []
            };
          }
          grouped[groupId].matches.push(match);
        }
      });

      return Object.values(grouped);
    }

    // Render combined groups view
    function renderGroups(standings, matches) {
      const container = document.getElementById("groups-container");
      container.innerHTML = '';

      if (!standings || standings.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: white; padding: 40px;">Nenhum dado encontrado</p>';
        return;
      }

      standings.forEach((group) => {
        const groupName = group.phase.names.default;
        const groupId = group.phase.id;

        // Find matches for this group
        const groupMatches = matches.filter(match => {
          const matchGroup = match.relatedSportsEvents?.find(e => e.typeDetail === 'PHASE_GROUP');
          return matchGroup && matchGroup.id === groupId;
        }).sort((a, b) => new Date(a.dateAndTimeInfo.scheduledStartTimeUTC) - new Date(b.dateAndTimeInfo.scheduledStartTimeUTC));

        // Create group wrapper
        const wrapperDiv = document.createElement("div");
        wrapperDiv.className = "group-wrapper";
        wrapperDiv.id = `group-${groupId}`;

        // Matches section
        const matchesDiv = document.createElement("div");
        matchesDiv.className = "group-matches";

        const matchesTable = document.createElement("table");
        matchesTable.className = "matches-table";

        const matchesHead = document.createElement("thead");
        matchesHead.innerHTML = `
      <tr>
        <th>Data</th>
        <th>Horário</th>
        <th>Mandante</th>
        <th colspan="3">Placar</th>
        <th>Visitante</th>
      </tr>
    `;
        matchesTable.appendChild(matchesHead);

        const matchesBody = document.createElement("tbody");
        matchesBody.className = "group-matches-list";

        groupMatches.forEach(match => {
          const tr = document.createElement("tr");
          tr.className = "match-card";
          tr.setAttribute('data-match-id', match.xid || match.id);

          try {
            const homeTeam = match.homeParticipant;
            const awayTeam = match.awayParticipant;

            const homeScore = match.result?.homeResult?.score ?? '-';
            const awayScore = match.result?.awayResult?.score ?? '-';
            const homeFlag = homeTeam?.nationality?.images?.flag || '';
            const awayFlag = awayTeam?.nationality?.images?.flag || '';
            const homeShort = homeTeam?.names?.abbreviation || homeTeam?.names?.short || homeTeam?.names?.default || 'A definir';
            const awayShort = awayTeam?.names?.abbreviation || awayTeam?.names?.short || awayTeam?.names?.default || 'A definir';

            const matchDateTime = new Date(match.dateAndTimeInfo.scheduledStartTimeUTC);
            const matchDate = matchDateTime.toLocaleString('pt-BR', {
              month: 'short',
              day: 'numeric'
            });
            const matchTime = matchDateTime.toLocaleString('pt-BR', {
              hour: '2-digit',
              minute: '2-digit'
            });

            tr.innerHTML = `
          <td class="match-date">${matchDate}</td>
          <td class="match-time">${matchTime}</td>
          <td class="match-team-home">
            <span>${homeShort}</span>
            <img src="${homeFlag}" alt="${homeShort}" class="match-flag" onerror="this.style.display='none'">
          </td>
          <td class="match-score"><span class="score-number">${homeScore}</span></td>
          <td class="match-vs">-</td>
          <td class="match-score"><span class="score-number">${awayScore}</span></td>
          <td class="match-team-away">
            <img src="${awayFlag}" alt="${awayShort}" class="match-flag" onerror="this.style.display='none'">
            <span>${awayShort}</span>
          </td>
        `;

            matchesBody.appendChild(tr);
          } catch (error) {
            console.error("Erro ao renderizar jogo:", match, error);
          }
        });

        matchesTable.appendChild(matchesBody);
        matchesDiv.appendChild(matchesTable);

        // Right side: standings
        const standingsDiv = document.createElement("div");
        standingsDiv.className = "group-standings";

        const standingsTitle = document.createElement("div");
        standingsTitle.className = "group-standings-title";
        standingsTitle.textContent = groupName;
        standingsDiv.appendChild(standingsTitle);

        const table = document.createElement("table");
        table.className = "group-table";

        const thead = document.createElement("thead");
        thead.innerHTML = `
      <tr>
        <th>Time</th>
        <th>J</th>
        <th>V</th>
        <th>E</th>
        <th>D</th>
        <th>GP</th>
        <th>GC</th>
        <th>SG</th>
        <th>Pontos</th>
      </tr>
    `;

        const tbody = document.createElement("tbody");

        group.participants
          .sort((a, b) => a.stats.rankSort - b.stats.rankSort)
          .forEach((team) => {
            const row = document.createElement("tr");
            row.innerHTML = `
          <td>
            <div class="team-info">
              <span class="position-badge">${team.stats.rankSort}</span>
              <img src="${team.nationality.images.flag}" alt="${team.names.default}" class="team-flag">
              <span>${team.names.default}</span>
            </div>
          </td>
          <td>${team.stats.matches}</td>
          <td>${team.stats.matchesWon}</td>
          <td>${team.stats.matchesDrawn}</td>
          <td>${team.stats.matchesLost}</td>
          <td>${team.stats.goalsFor}</td>
          <td>${team.stats.goalsAgainst}</td>
          <td>${team.stats.goalsDifference}</td>
          <td><span class="points">${team.stats.points}</span></td>
        `;
            tbody.appendChild(row);
          });

        table.appendChild(thead);
        table.appendChild(tbody);
        standingsDiv.appendChild(table);

        // Add both sides to wrapper (standings first, then matches)
        wrapperDiv.appendChild(standingsDiv);
        wrapperDiv.appendChild(matchesDiv);
        container.appendChild(wrapperDiv);
      });
    }

    // Fetch today's matches only
    async function fetchTodayMatches() {
      try {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        const timeFrom = today.toISOString();
        const timeTo = tomorrow.toISOString();

        const response = await fetch(
          `https://api.sports.gracenote.com/gsd/lookup/v1/leagues/GNA5MGHG2ZVX2RD/schedule-results?timeFrom=${timeFrom}&timeTo=${timeTo}&language=pt-BR&showXids=true&api_key=e7zxsg4hhbfe4cg52bqa2pxs`
        );

        if (!response.ok) throw new Error("Falha ao buscar jogos de hoje");
        const data = await response.json();
        return data.matches || [];
      } catch (error) {
        console.error("Erro ao buscar jogos de hoje:", error);
        return [];
      }
    }

    // Update today's matches in DOM
    function updateTodayMatches(todayMatches) {
      const groupsContainer = document.getElementById("groups-container");

      todayMatches.forEach(match => {
        const matchId = match.xid || match.id;
        const matchCard = groupsContainer.querySelector(`[data-match-id="${matchId}"]`);

        if (matchCard) {
          const homeScore = match.result?.homeResult?.score ?? '-';
          const awayScore = match.result?.awayResult?.score ?? '-';

          const scoreDiv = matchCard.querySelector('.score');
          if (scoreDiv) {
            const scores = scoreDiv.querySelectorAll('.score-number');
            scores[0].textContent = homeScore;
            scores[1].textContent = awayScore;
          }
        }
      });
    }

    // Poll for updates every 1 minute
    function startPolling() {
      setInterval(async () => {
        const [standings, todayMatches] = await Promise.all([
          fetchStandings(),
          fetchTodayMatches()
        ]);

        renderGroups(standings, allMatches);
        updateTodayMatches(todayMatches);
      }, 60000);
    }

    // Initialize
    Promise.all([
      fetchStandings(),
      fetchMatches()
    ]).then(([standings, matches]) => {
      allMatches = matches;
      renderGroups(standings, allMatches);
      startPolling();
    });

    // Print table function
    function printTable() {
      window.print();
    }

    // Export to Excel function
    function exportToExcel() {
      const container = document.getElementById('groups-container');
      const groups = container.querySelectorAll('.group-wrapper');

      let excelData = [['Copa do Mundo FIFA 2026 - Classificação e Jogos']];
      excelData.push(['']);

      groups.forEach(group => {
        const groupName = group.querySelector('.group-standings-title').textContent;
        excelData.push([groupName]);
        excelData.push(['Posição', 'Time', 'J', 'V', 'E', 'D', 'GP', 'GC', 'SG', 'Pontos']);

        const table = group.querySelector('.group-table tbody');
        if (table) {
          const rows = table.querySelectorAll('tr');
          rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
              const rowData = [];
              cells.forEach(cell => {
                const text = cell.textContent.trim();
                rowData.push(text);
              });
              excelData.push(rowData);
            }
          });
        }

        excelData.push(['']);
        excelData.push(['Data', 'Horário', 'Mandante', 'Placar', 'Visitante']);

        const matchesTable = group.querySelector('.matches-table tbody');
        if (matchesTable) {
          const matchRows = matchesTable.querySelectorAll('tr');
          matchRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
              const matchData = [];
              cells.forEach((cell, index) => {
                if (index < 5) {
                  const text = cell.textContent.trim();
                  matchData.push(text);
                }
              });
              excelData.push(matchData);
            }
          });
        }

        excelData.push(['']);
        excelData.push(['']);
      });

      // Create CSV content
      let csvContent = '\uFEFF'; // BOM for UTF-8
      excelData.forEach(row => {
        csvContent += row.map(cell => {
          const cellValue = cell.toString().replace(/"/g, '""');
          return `"${cellValue}"`;
        }).join(',') + '\n';
      });

      // Create download link
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', 'copa_mundo_2026.csv');
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
