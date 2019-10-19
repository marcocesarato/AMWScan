import React from "react";
import {Link} from "react-router-dom";
import {Button} from "react-bootstrap";
import {ModalEditor} from "../../components/ModalEditor";
import {userService} from "../../services";

import "brace/theme/github";

class HomePage extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			user: {},
			compare: false,
			showModal: false,
			code: "",
			codeReadOnly: "",
		};
	}

	componentDidMount() {
		this.setState({
			user: JSON.parse(localStorage.getItem("user")),
		});
		userService.getAll().then((users) => this.setState({users}));
	}

	close() {
		this.setState({showModal: false});
	}

	open() {
		this.setState({showModal: true});
	}

	render() {
		const {user} = this.state;
		return (
			<div className="col-md-6 col-md-offset-3">
				<h1>Hi {user.firstName}!</h1>
				<Button variant="primary" onClick={this.open.bind(this)}>
					Launch demo modal
				</Button>
				<Link to="/login">
					<Button variant="danger">Logout</Button>
				</Link>

				<ModalEditor
					value={"wewewe"}
					open={this.state.showModal}
					showOptions={true}
					onOpen={this.open.bind(this)}
					onClose={this.close.bind(this)}
				/>
			</div>
		);
	}
}

export {HomePage};
