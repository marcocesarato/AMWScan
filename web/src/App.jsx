import React from "react";
import {BrowserRouter as Router, Route} from "react-router-dom";
import {PrivateRoute} from "./components";
import {HomePage} from "./screens/HomePage";
import {LoginPage} from "./screens/LoginPage";

window.users = [
	{
		id: 1,
		username: "test",
		password: "test",
		firstName: "Test",
		lastName: "User",
	},
];

class App extends React.Component {
	render() {
		return (
			<div>
				<div className="jumbotron">
					<h1 className="text-center">Antimalware Scanner</h1>
				</div>
				<div className="container">
					<Router>
						<div>
							<PrivateRoute exact path="/" component={HomePage} />
							<Route path="/login" component={LoginPage} />
						</div>
					</Router>
				</div>
			</div>
		);
	}
}

export {App};