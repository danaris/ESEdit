// whist.js

var players = [];
var playerOrder = [];
var myId = -1;
var iAmJoining = false;
var gameId = -1;
var playerEntryCount = 0;
var createUrl = '/whist/control/createGame';
var joinUrl = '/whist/control/joinGame';
var startUrl = '/whist/control/startGame/';
var checkGatherStateUrl = '/whist/data/checkGatherState/';
var gameStateUrl = '/whist/data/gameState/'
var joinerUpdateTimer;
var gatherUpdateTimer;

var curRound = null;

var colors = ["lightcoral", "crimson", "red", "firebrick", "darkred", "mediumvioletred", "tomato", "orange", "forestgreen", "limegreen", "darkgreen", "darkolivegreen", "lightseagreen", "darkcyan", "cadetblue", "steelblue", "dodgerblue", "royalblue", "blue", "darkblue", "orchid", "blueviolet", "indigo", "tan", "darkgoldenrod", "saddlebrown", "maroon", "slategray", "darkslategray"];

var usedColors = [];

function newPlayer() {
	$("#welcome").dialog("close");
	$("#newPlayer").dialog();
	var paletteColors = [];
	var j = -1;
	var k = 0;
	for (var i in colors) {
		if (usedColors.includes(colors[i])) {
			continue;
		}
		if (k % 5 == 0) {
			j++;
			paletteColors[j] = [];
		}
		paletteColors[j].push(colors[i]);
		k++;
	}
	var startColor = paletteColors[0][0];
	$("#newPlayerColor").spectrum({
		preferredFormat: "name",
		showPaletteOnly: true,
		showPalette: true,
		color: startColor,
		palette: paletteColors
	});
}

function updateSelectPlayerColor() {
	var playerId = $("#selectPlayerList").val();
	if (playerId == -1) {
		return newPlayer();
	}
	$("#selectPlayerColor").css('background-color', players[playerId].color);
}

function createPlayer() {
	var playerName = $("#newPlayerName").val();
	var playerColor = $("#newPlayerColor").val();
	$.ajax({
		type: "GET",
		url: "/whist/control/createPlayer",
		data: { playerName: playerName, playerColor: playerColor },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				$("#newPlayer").dialog("close");
				var me = {name: playerName, color: playerColor, id: data.id};
				players[data.id] = me;
				myId = data.id;
				$("#newGameText").html('<span style="color: '+me.color+';">'+me.name+'</span>, what do you want to do?');
				$("#newGame").dialog();
			}
		},
		dataType: 'json'
	});
}

function selectPlayer() {
	var playerId = $("#selectPlayerList").val();
	$.ajax({
		type: "GET",
		url: "/whist/control/selectPlayer",
		data: { playerId: playerId },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				$("#welcome").dialog("close");
				var me = players[data.id];
				myId = data.id;
				if (data.gameId != -1) {
					getGameInProgress(data.gameId);
				} else {
					$("#newGameText").html('<span style="color: '+me.color+';">'+me.name+'</span>, what do you want to do?');
					$("#newGame").dialog();
				}
			}
		},
		dataType: 'json'
	});
}

function showGameNameDialog() {
	$("#newGame").dialog("close");
	$("#createGame").dialog({
		minWidth: 350
	});
	// TODO: On close of createGame, return to newGame
}

function createGame() {
	var gameName = $("#newGameName").val();
	$.ajax({
		type: "GET",
		url: createUrl,
		data: { gameName: gameName },
		success: function (data) {
			if (data.error) {
				$("#createGameErrors").text(data.error);
			} else {
				$("#createGame").dialog("close");
				// TODO: fill in #gatherGame
				gameId = data.gameId;
				$("#gatherGame").dialog();
				$("#gatherGameName").html(gameName);
				gatherUpdateTimer = setTimeout(function() { updateGatherList(); }, 1000);
			}
		},
		dataType: 'json'
	});
}

function updateGatherList() {
	$.ajax({
		type: "GET",
		url: checkGatherStateUrl + gameId,
		data: { },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				var playerList = '';
				for (var i in data.players) {
					var playerId = data.players[i];
					var player = players[playerId];
					playerList += '<li style="color: '+player.color+';">'+player.name+'</li>';
				}
				if (data.players.length >= 3 && data.players.length <= 7) {
					$("#startGameButton").prop('disabled',false);
				} else {
					$("#startGameButton").prop('disabled',true);
				}
				$("#gatherPlayerNames").html(playerList);
			}
			gatherUpdateTimer = setTimeout(function() { updateGatherList(); }, 1000);
		},
		dataType: 'json'
	})
}

function updateJoinPlayerList() {
	console.log("About to check gather state for game ID "+gameId);
	$.ajax({
		type: "GET",
		url: checkGatherStateUrl + gameId,
		data: { },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				var playerList = '';
				for (var i in data.players) {
					var playerId = data.players[i];
					var player = players[playerId];
					playerList += '<li style="color: '+player.color+';">'+player.name+'</li>';
				}
				$("#joinPlayerNames").html(playerList);
			}
			joinerUpdateTimer = setTimeout(function() { updateJoinPlayerList(); }, 1000);
		},
		dataType: 'json'
	})
}

function showJoinDialog() {
	$("#newGame").hide();
	$("#joinGame").dialog();
	// TODO: On close of joinGame, return to newGame
	joinerUpdateTimer = setTimeout(function() { joinerUpdateGameList(); }, 1000);
}

function joinerUpdateGameList() {
	$.ajax({
		type: "GET",
		url: '/whist/control/updateGameList',
		data: {  },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				var selected = $("input[name=joinGameId]:checked").val();
				var html = '';
				if (data.games.length > 0) {
					for (var i in data.games) {
						var game = data.games[i];
						var checked = '';
						if (selected == game.id) {
							checked = ' checked';
						}
						html += '<div><input type="radio" name="joinGameId" value="'+game.id+'"'+checked+'>'+game.name+'</div>';
					}
					$("#joinGameList").html(html);
				}
			}
			joinerUpdateTimer = setTimeout(function() { joinerUpdateGameList(); }, 1000);
		},
		dataType: 'json'
	});
}

function joinGame() {
	var joinGameId = $("input[name=joinGameId]:checked").val();
	var playerName = $("#joinGamePlayerName").val();
	$.ajax({
		type: "GET",
		url: joinUrl,
		data: { gameId: joinGameId, myName: playerName },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				$("#joinGame").dialog("close");
				// TODO: fill in #gatherGame
				gameId = data.gameId;
				console.log("Updated game ID to "+gameId);
				iAmJoining = true;
				$("#waitForGame").dialog();
				clearTimeout(joinerUpdateTimer);
				joinerUpdateTimer = setTimeout(function() { updateJoinPlayerList(); }, 1000);
			}
		},
		dataType: 'json'
	});
}

function cancelJoin() {
	$.ajax({
		type: "GET",
		url: "/whist/control/cancelJoin",
		data: { },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				$("#waitForGather").dialog("close");
				$("#newGame").dialog();
				clearTimeout(joinerUpdateTimer);
			}
		},
		dataType: 'json'
	});
}

function startGame() {
	// Wait, no, this is wrong: I need to get the player names from each individual computer
	// This will require giving the *game* a name (and possibly a password?), and polling on each browser that has clicked "Join Game"
	$.ajax({
	type: "GET",
	url: startUrl + gameId,
	data: { },
	success: function (data) {
		if (data.error) {
			// TODO: errors
		} else {
			$("#gatherGame").dialog("close");
			for (var i in data.players) {
				var player = data.players[i];
				players[player.id] = {id: player.id, name: player.name, color: player.color};
				playerOrder.push(player.id);
			}
			if (playerOrder[0] != myId) {
				let myIndex = playerOrder.indexOf(myId);
				let interloper = playerOrder[0];
				playerOrder[0] = myId;
				playerOrder[myIndex] = interloper;
			}
			clearTimeout(gatherUpdateTimer);
			$("#whistGame").css('display','');
			$("#myHand").css('border-color',players[myId].color);
			for (var i=1; i<playerOrder.length; i++) {
				let playerNum = i+1;
				if (i > 2) {
					$("#topPlayers").append('<div id="player'+playerNum+'" class="playerContainer"></div>');
				}
				$("#player"+playerNum).css('border-color',players[playerOrder[i]].color).css('color',players[playerOrder[i]].color);
				$("#player"+playerNum).append('<div class="playerName">'+players[playerOrder[i]].name+'</div><div id="player'+playerNum+'Next"></div><div id="player'+playerNum+'Cards"></div><div id="player'+playerNum+'Deal"></div><div class="playerScore" id="player'+playerNum+'Score"></div>');
			}
			$("#status").text("Waiting for "+players[myId].name);
		}
	},
	dataType: 'json'
});
}

function getGameInProgress(gameId) {
	$.ajax({
		type: "GET",
		url: gameStateUrl + gameId + '/' + myId,
		data: { },
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				$("#whistGame").css('display','');
				// Get the player information from the response
				for (var i in data.players) {
					var player = data.players[i];
					players[player.id] = player;
					playerOrder[i] = player.id;
				}
				$("#myHand").css('border-color',data.players[myId].color);
				// Display the players
				for (var i=1; i<playerOrder.length; i++) {
					let playerNum = i+1;
					if (i > 2) {
						$("#topPlayers").append('<div id="player'+playerNum+'" class="playerContainer"></div>');
					}
					$("#player"+playerNum).css('border-color',players[playerOrder[i]].color).css('color',players[playerOrder[i]].color);
					$("#player"+playerNum).append('<div class="playerName">'+players[playerOrder[i]].name+'</div><div id="player'+playerNum+'Next"></div><div class="otherPlayerCards" id="player'+playerNum+'Cards"></div><div id="player'+playerNum+'Deal"></div><div class="playerScore" id="player'+playerNum+'Score"></div>');
					for (var j=0; j<players[playerOrder[i]].handCount; j++) {
						var left = j * 0.28;
						$("#player"+playerNum+"Cards").append('<div id="player'+playerNum+'Card'+(j+1)+'" class="otherCard otherCardContainer" style="left: '+left+'em;"><img class="otherCard" src="https://deckofcardsapi.com/static/img/back.png"></div>');
					}
					
				}
				// Display your hand
				if (players[myId].hand != undefined && players[myId].hand.length > 0) {
					for (var c in players[myId].hand) {
						var card = players[myId].hand[c];
						$("#myHand").append('<div class="handCard" id="myHandCard'+c+'" style="background: url('+card.image+') no-repeat;"><div class="handCardCover" id="myHandCard'+c+'cover"></div></div>');
						$("#myHandCard"+c).bind('mouseover',onHandCardHover);
						$("#myHandCard"+c).bind('mouseout',onHandCardUnhover);
					}
				}
				// Get the round information—what's the current game status?
				curRound = data.round;
				$("#round").text('Round '+curRound.index+': '+curRound.name);
				// If we have fewer bids than players, it's still the bidding phase
				if (curRound.bids.length < data.players.length) {
					var curBidPlayer = players[playerOrder[curRound.bids.length]];
					$("#status").text(curBidPlayer.name+" to Bid");
				}
			}
		},
		dataType: 'json'
	});
}

$(function() {
	$("#welcome").dialog({
		minWidth: 400,
		open: function(event, ui) {
			$.ajax({
				type: "GET",
				url: "/whist/data/playerList",
				data: {},
				success: function (data) {
					if (data.error) {
						// TODO: errors
					} else {
						$("#selectPlayerList").html('');
						for (var i in data.players) {
							var player = data.players[i];
							players[player.id] = player;
							$("#selectPlayerList").append('<option value="'+player.id+'">'+player.name+'</option>');
							usedColors.push(player.color);
						}
						$("#selectPlayerColor").css('background-color',data.players[0].color);
						$("#selectPlayerList").append('<option value="-1">New Player...</option>');
					}
				},
				dataType: 'json'
			})
		}
	});
	$("#welcome").show();
	$("#newGameName").keypress(function(e) {
		if (e.keyCode == 13) {
			createGame();
		}
	});
});

// Event listeners

function onHandCardHover(event) {
	var cardDiv = event.target;
	$(cardDiv).css('background-color','rgba(128,128,255,0.3);');
}

function onHandCardUnhover(event) {
	var cardDiv = event.target;
	$(cardDiv).css('background-color','');
	
}