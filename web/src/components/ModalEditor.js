import React from "react";
import {Modal, Button, Row, Col} from "react-bootstrap";
import AceEditor from "react-ace";
import "./ModalEditor.css";

import "brace/theme/github";

class ModalEditor extends React.Component {
	constructor(props) {
		super(props);

		this.initialState = {
			open: !!props.open,
			value: "",
			mode: "text",
			readOnly: false,
			showOptions: false,
		};
		this.state = this.initialState;
	}

	componentDidMount() {}

	componentDidUpdate(prevProps) {
		if (this.props.open !== prevProps.open) {
			this.setState({open: this.props.open});
		}
		if (this.props.value !== prevProps.value) {
			this.setState({value: this.props.value});
		}
		if (this.props.mode !== prevProps.mode) {
			this.setState({mode: this.props.mode});
		}
		if (this.props.readOnly !== prevProps.readOnly) {
			this.setState({readOnly: this.props.readOnly});
		}
	}

	close() {
		if (this.props.onClose) {
			this.props.onClose();
		}
		this.setState({...this.initialState, open: false});
	}

	open() {
		if (this.props.onOpen) {
			this.props.onOpen();
		}
		this.setState({open: true});
	}

	onChange(value) {
		this.setState({value: value});
	}

	render() {
		const editorOptions = {
			theme: "github",
			name: "editor",
			mode: this.state.mode,
			onChange: this.onChange.bind(this),
			fontSize: 16,
			showPrintMargin: true,
			showGutter: true,
			highlightActiveLine: true,
			width: "100%",
			readOnly: this.state.readOnly,
			setOptions: {
				enableBasicAutocompletion: true,
				enableLiveAutocompletion: true,
				enableSnippets: true,
				showLineNumbers: true,
				tabSize: 2,
			},
		};

		return (
			<div>
				<Modal
					className="modal-container modal-full"
					show={this.state.open}
					onHide={this.close.bind(this)}>
					<Modal.Header closeButton>
						<Modal.Title>Modal heading</Modal.Title>
					</Modal.Header>
					<Modal.Body>
						<h4>Text in a modal</h4>
						<p>
							Duis mollis, est non commodo luctus, nisi erat
							porttitor ligula.
						</p>

						{this.props.showOptions ? (
							<div>
								<Button
									variant="info"
									size="small"
									className={"mr-1"}
									onClick={() => {
										alert("do stuff");
									}}>
									Edit File
								</Button>
								<Button
									variant="success"
									size="small"
									className={"mr-1"}
									onClick={() => {
										alert("do stuff");
									}}>
									Remove code
								</Button>
								<Button
									variant="success"
									size="small"
									className={"mr-1"}
									onClick={() => {
										alert("do stuff");
									}}>
									Remove line
								</Button>
								<Button
									variant="success"
									size="light"
									className={"mr-1"}
									onClick={() => {
										alert("do stuff");
									}}>
									Add to Whitelist
								</Button>
								<Button
									variant="warning"
									size="small"
									className={"mr-1"}
									onClick={() => {
										alert("do stuff");
									}}>
									Move to Quarantine
								</Button>
								<Button
									variant="danger"
									className={"mr-1"}
									onClick={() => {
										alert("do stuff");
									}}>
									Delete File
								</Button>
								<Button
									variant="secondary"
									size="small"
									className={"mr-1"}
									onClick={() => {
										alert("do stuff");
									}}>
									Ignore File
								</Button>
							</div>
						) : (
							[]
						)}

						<div className={"mt-3"}>
							{Array.isArray(this.state.value) ? (
								<Row>
									<Col>
										<h4>Original</h4>
										<AceEditor
											{...editorOptions}
											readOnly={true}
											value={this.state.value[0]}
										/>
									</Col>
									<Col>
										<h4>Fixed</h4>
										<AceEditor
											{...editorOptions}
											readOnly={true}
											value={this.state.value[1]}
										/>
									</Col>
								</Row>
							) : (
								<div>
									<h4>Source</h4>
									<AceEditor
										{...editorOptions}
										value={this.state.value}
									/>
								</div>
							)}
						</div>
					</Modal.Body>
					<Modal.Footer>
						<Button onClick={this.close.bind(this)}>Close</Button>
					</Modal.Footer>
				</Modal>
			</div>
		);
	}
}

export {ModalEditor};
